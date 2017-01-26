<?php

namespace api\models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Transaksi extends Eloquent {
	protected $table = 'transaksi'; //with this, remove auto 's' in generated query table

	public $timestamps = false; //By default, Eloquent will maintain the created_at and updated_at columns on your database table automatically. Set false to disabled. 

	public function getTanggalTransaksi() {
		return $this->trans_tanggal_transaksi;
	}
}