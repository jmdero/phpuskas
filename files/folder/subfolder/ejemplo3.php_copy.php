<?php

//$_SERVER['DOCUMENT_ROOT']='/home/websites/webs/trenes_dv5/legacy';

//include('../../includes.php');

$_SERVER['HTTP_HOST']='dv5.trenes.com';

$_SERVER['SERVER_ADDR']='5.39.74.9';

$IdSesion=uniqid();

$saveLogDB=1;

//---------------------------------- Busquedas

//------ findTrips.

$idEstacionOrigen=8000262; //Munich - Ost

$idEstacionDestino=8004168;//Munich - Flughafen Terminal 

date_default_timezone_set('Europe/Madrid');

$fechaFutura='2 weeks';

$fechaViaje=new DateTime($fechaFutura);

$fechaViaje=str_replace(' ','T',date_format($fechaViaje,'Y-m-d H:i:s'));

//$fechaInicio=str_replace(' ','T',date_format($fechaInicio,'Y-m-d H:i:s'));

//------ findTrips.



//----------------------- Integración DeutscheBahn ----------------------------//

$DBTestPath='/../..';

include_once dirname(__DIR__).$DBTestPath.'/src/Libraries/DeutscheBahn/DeutscheBahn.php'; //Abra que modifiocarla

use App\Libraries\DeutscheBahn as DeutscheBahn;

$DeutscheBahn= new DeutscheBahn();

// $DeutscheBahn->connectAuth();

// $DeutscheBahn->findTrips($idEstacionOrigen,$idEstacionDestino,$fechaViaje);



//--- Este orden es importante cada uno depende del anterior.

$DeutscheBahn->getOffers($idEstacionOrigen,$idEstacionDestino,$fechaViaje);

$DeutscheBahn->generateBasketId();

// $DeutscheBahn->setBooking();

// $DeutscheBahn->processBooking();

// $DeutscheBahn->getBooking();

// $DeutscheBahn->refund();

//---

// $DeutscheBahn->getBasket();

// $DeutscheBahn->deleteBasketItems();

// $DeutscheBahn->createTicket();





// ---- funciones auxiliares

// $DeutscheBahn->getMasterData('bikeCarriageType');

// $DeutscheBahn->getMasterData('comfortClass');

// $DeutscheBahn->getMasterData('passengerType');

// $DeutscheBahn->getMasterData('reductionType');

// $DeutscheBahn->getMasterData('productType');



//------ funciones desactivadas

// $DeutscheBahn->bestPriceOffer($idEstacionOrigen,$idEstacionDestino,$fechaViaje);//Este de momento está capado

// $stationsList=$DeutscheBahn->getLocations();

// var_dump($stationsList);