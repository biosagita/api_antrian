<?php
use api\models\Transaksi;
use api\lib\Helpers;
use api\lib\MemoryStat;

//----if "not found" 404---------
$app->notFound(function () use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 404,
		'message' => 'Not found'
	];
	$message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//----if "system error" 500---------
$app->error(function (\Exception $e) use ($app) {
    $app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 500,
		'message' => 'System Error'
	];
	$message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/sisa/:addressCaller----------
$app->get('/v1/transaksi/sisa/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
	];

	$caller = $app->db->table('caller')
	->select('id_caller')
	->where('address_caller', $addressCaller)
	->take(1)
	->get();

	if($caller) {
		foreach ($caller as $key => $value) {
			$transaksi = $app->db->table('transaksi')
			->select('transaksi.no_ticket_awal', 'transaksi.no_ticket')
			->where('transaksi.status_transaksi', 2)
			->where('transaksi.id_caller', $value['id_caller'])
			->where('transaksi.tanggal_transaksi', 'CURDATE() + 0')
			->take(1)
			->get();

			if($transaksi) {
				foreach ($transaksi as $key => $value) {
					$data['result']['noAntrian'] = $value['no_ticket_awal'].$value['no_ticket'];
				}
			}
		}
	}

	$sisaTransaksi = $app->db->table('caller')
	->select('caller.id_caller')
	->leftJoin('loket', 'caller.id_loket', '=', 'loket.id_loket')
	->leftJoin('prioritas_layanan', 'loket.id_group_loket', '=', 'prioritas_layanan.id_group_loket')
	->leftJoin('transaksi', 'prioritas_layanan.id_group_layanan', '=', 'transaksi.id_group_layanan')
	->where('transaksi.status_transaksi', 0)
	->where('caller.address_caller', $addressCaller)
	->where('transaksi.tanggal_transaksi', 'CURDATE() + 0')
	->count();

	$data['result']['jumlah'] = $sisaTransaksi;

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/next/:addressCaller----------
$app->get('/v1/transaksi/next/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
	];

	//--------------Cek status transaksi sebelumnya pada address yang sama, bila masih ada yang status = 1, maka tahan dulu-----------
	$transaksi = $app->db->table('transaksi')
	->select('transaksi.id_transaksi')
	->leftJoin('loket', 'transaksi.id_loket', '=', 'loket.id_loket')
	->leftJoin('layanan', 'transaksi.id_layanan', '=', 'layanan.id_layanan')
	->leftJoin('caller', 'transaksi.id_caller', '=', 'caller.id_caller')
	->where('transaksi.status_transaksi', 1)
	->where('caller.address_caller', $addressCaller)
	->where('transaksi.tanggal_transaksi', 'CURDATE() + 0')
	->take(1)
	->get();

    if($transaksi) {
    	foreach ($transaksi as $key => $value) {
	    	$data['message'] = 'Status ID Transaksi = ' . $value['id_transaksi'] . ', Masih bernilai 1';
	    }
    } else {
    	//--------------Cek status transaksi = 2 pada address yang sama, bila ada maka update menjadi 5-----------
    	$transaksi = $app->db->table('transaksi')
    	->select('transaksi.no_ticket', 'transaksi.no_ticket_awal', 'layanan.id_layanan_forward', 'transaksi.id_transaksi')
		->leftJoin('loket', 'transaksi.id_loket', '=', 'loket.id_loket')
		->leftJoin('layanan', 'transaksi.id_layanan', '=', 'layanan.id_layanan')
		->leftJoin('caller', 'transaksi.id_caller', '=', 'caller.id_caller')
		->where('transaksi.status_transaksi', 2)
		->where('caller.address_caller', $addressCaller)
		->where('transaksi.tanggal_transaksi', 'CURDATE() + 0')
		->take(1)
		->get();

		if($transaksi) {
			foreach ($transaksi as $key => $value) {
				//------update transaksi status = 2 menjadi 5 (status "selesai")---------
				$idTransaksi = $value['id_transaksi'];
				$res = $app->db->table('transaksi')
				->where('id_transaksi', $idTransaksi)
				->update([
					'status_transaksi' => 5,
					'waktu_finish' => 'CURTIME()',
				]);

				//------check forward, bila ada maka insert menjadi transaksi baru------
				$forward = $value["id_layanan_forward"];
				if(!empty($forward)) {
					$layanan = $app->db->table('layanan')
					->select('id_group_layanan')
					->where('id_layanan', $forward)
					->get();

					if($layanan) {
						$noTicket = $value["no_ticket"];
						$noTicketAwal = $value["no_ticket_awal"];
						$idGroupLayanan = $value["id_group_layanan"];
			            $tanggalTransaksi  = date('Ymd');
			            $waktuForward    = date("H:i:s");
			            $res = $app->db->table('transaksi')
			            ->insert([
			            	'tanggal_transaksi' => $tanggalTransaksi,
			            	'waktu_ambil' => $waktuForward,
			            	'no_ticket_awal' => $noTicketAwal,
			            	'no_ticket' => $noTicket,
			            	'id_layanan' => $forward,
			            	'id_group_layanan' => $idGroupLayanan,
			            	'status_transaksi' => 0,
			            ]);
					}
				}
			}

			//--------------Cek status transaksi = 0 pada address yang sama, bila ada maka update menjadi 1-----------
			$transaksi = $app->db->table('transaksi')
	    	->select('transaksi.no_ticket', 'transaksi.id_group_layanan', 'transaksi.id_transaksi', 'caller.id_caller', 'caller.id_loket')
	    	->leftJoin('prioritas_layanan', 'transaksi.id_group_layanan', '=', 'prioritas_layanan.id_group_layanan')
			->leftJoin('loket', 'prioritas_layanan.id_group_loket', '=', 'loket.id_group_loket')
			->leftJoin('caller', 'loket.id_loket', '=', 'caller.id_loket')
			->where('transaksi.status_transaksi', 0)
			->where('caller.address_caller', $addressCaller)
			->where('transaksi.tanggal_transaksi', 'CURDATE() + 0')
			->orderBy('prioritas_layanan.Prioritas, transaksi.waktu_ambil')
			->take(1)
			->get();

			if($transaksi) {
				foreach ($transaksi as $key => $value) {
					$idTransaksi = $value["id_transaksi"];
					$idCaller = $value["id_caller"];
					$idLoket = $value["id_loket"];

	         		$res = $app->db->table('transaksi')
					->where('id_transaksi', $idTransaksi)
					->update([
						'status_transaksi' => 1,
						'waktu_panggil' => 'CURTIME()',
						'id_caller' => $idCaller,
						'id_loket' => $idLoket,
					]);

					$data['result']['idTransaksi'] = $idTransaksi;
				}
			}
		} else {
			$data['message'] = 'Result is empty!';
		}
    }

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/status/:addressCaller----------
$app->get('/v1/transaksi/status/:addressCaller/:idTransaksi', function ($addressCaller, $idTransaksi) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
		'noAntrian' => 'A1',
		'status' => 1, //status 0=new, 1=next, 2=sound caller, 3=skip, 5=done
	];

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/recall/:addressCaller----------
$app->get('/v1/transaksi/recall/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
		'idTransaksi' => 1,
	];

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/skip/:addressCaller----------
$app->get('/v1/transaksi/skip/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
		'status' => 'ok',
	];

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/finish/:addressCaller----------
$app->get('/v1/transaksi/finish/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
		'status' => 'ok',
	];

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});

//-----API v1 routes GET /v1/transaksi/nextManual/:addressCaller----------
$app->get('/v1/transaksi/nextManual/:addressCaller', function ($addressCaller) use ($app) {
	$app->response->headers->set('Content-Type','application/json');

	$data = [
		'code' => 200,
		'idTransaksi' => 'A1',
	];

    $message = Helpers::convertToJSONPretty($data);
	echo $message;
});