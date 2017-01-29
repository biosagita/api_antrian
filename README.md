# api_antrian
API Antrian

## URL END POINT:
* GET /v1/transaksi/sisa/:addressCaller [TESTING]

* GET /v1/transaksi/next/:addressCaller [TESTING]

* GET /v1/transaksi/status/:addressCaller [TESTING]

* GET /v1/transaksi/recall/:addressCaller [TESTING]

* GET /v1/transaksi/skip/:addressCaller [TESTING]

* GET /v1/transaksi/finish/:addressCaller [TESTING]

* GET /v1/transaksi/nextManual/:addressCaller/:noTicket [TESTING]


## KONFIGURASI DATABASE:
File config ada di file "root/config/database.php"


## SAMPLE URL:
  * Request URL : http://localhost/api_antrian/v1/transaksi/next/1

	Response Server :
	{
	    "code": 200,
	    "message": "Result is empty!"
	}

  * Request URL : http://localhost/api_antrian/v1/transaksi/sisa/1

  * Request URL : http://localhost/api_antrian/v1/transaksi/status/1/18

  * Request URL : http://localhost/api_antrian/v1/transaksi/recall/1

  * Request URL : http://localhost/api_antrian/v1/transaksi/skip/1

  * Request URL : http://localhost/api_antrian/v1/transaksi/finish/1

  * Request URL : http://localhost/api_antrian/v1/transaksi/nextManual/1/1