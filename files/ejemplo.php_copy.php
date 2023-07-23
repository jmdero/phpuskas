<?php

namespace App\Libraries;

require_once dirname ( __DIR__ ) . "/Provider/Provider.php";

require_once dirname ( __DIR__ ) . "/DeutscheBahn/DeutscheBahnAuth.php";

require_once dirname ( __DIR__ ) . "/DeutscheBahn/DeutscheBahnOffers.php";

?><script type = "text/javascript">

  console . log ( "test" );

</script>

<style>

  html

  
  {
    display : block;
  }

</style>

<div>

  <p> Test </p>

  <div><?php echo "nada";?></div>

 

</div>

<?php

require_once dirname ( __DIR__ ) . "/DeutscheBahn/DeutscheBahnCurl.php";

use App\Libraries\Provider as Provider;

use App\Libraries\DeutscheBahn\DeutscheBahnAuth as DeutscheBahnAuth;

use App\Libraries\DeutscheBahn\DeutscheBahnOffers as DeutscheBahnOffers;

use App\Libraries\DeutscheBahn\DeutscheBahnCurl as DeutscheBahnCurl;

use ErrorException;

class DeutscheBahn extends Provider
{
    protected string $basketId;

    protected array $basketDataCurl;

    protected array $bookingDataCurl;

    protected array $offersIds;

    protected string $offerBundleId;

    protected string $name = 'DeutscheBahn';

    protected function postConstructor () 
    {
      $this->auth = new DeutscheBahnAuth ( $this->name, $this->dataRoute ) ;
    }

    public function createTicket () 
    {
        global $VSesiones;

        global $saveLogDB;

        $fulfillmentId = '2749664791101440';

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'ticketDataCurl' );

        $header = $this->defaultCurlHeader ( true );

        $url = str_replace ( ['{bookingId}', '{fulfillmentId}'], [$VSesiones["busquedaTR"]['DeutscheBahInitialBookingId'], $fulfillmentId], $dataCurl['url'] );

        $curlOptions = $this->setCurlOptions ( $url, 'POST', $header );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $this->bookingDataCurl['url'], $response ) : '';

        $this->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        var_dump ( $arrayResponse );
    }

    public function deleteBasketItems () 
    {
        global $saveLogDB;

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'deleteBasketDataCurl' );

        $header = $this->defaultCurlHeader ();

        $url = str_replace ( '{basketId}', $this->basketId, $dataCurl['url'] );

        $url .= '?serviceItems=[0:"'.$this->offersIds[0].'"]';

        $curlOptions = $this->setCurlOptions ( $url, 'DELETE', $header );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $this->basketDataCurl['url'], $response ) : '';

        var_dump ( $response );

        $arrayResponse = json_decode ( $response, true );

        var_dump ( $arrayResponse );
    }

    /**

     * Busca los viajes entre dos estaciones .

     * @param int $startStationId

     * @param int $endStationId

     * @param string $startDate

     */

    public function findTrips ( int $startStationId, int $endStationId, string $tripDate ) 
     {
        global $saveLogDB;

        parent :: checkAndConnect ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'findTripsDataCurl' );

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $DBCurl->defaultCurlHeader ( $this->auth, true );

        $postfields = '
        {
          "dateTime": "'.$tripDate.'",

          "originId": "'.$startStationId.'",

          "destinationId": "'.$endStationId.'"

        }';

        $DBCurl->setProperties ( ['postfields' => $postfields, 'url' => $dataCurl['url'], 'method' => 'POST'] );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        ( $saveLogDB ) ? $DBCurl->insertLog ( $response ) : '';

        $DBCurl->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        var_dump ( array_keys ( $arrayResponse['tripsCollections'][0] ) );
    }

    public function getBasket () 
    {
        global $saveLogDB;

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        ( empty ( $basketDataCurl ) ) ? parent :: setEnviorentmentProperties ( 'basketDataCurl' ) : '';

        $header = $this->defaultCurlHeader ();

        $curlOptions = $this->setCurlOptions ( $this->basketDataCurl['url'] . '/' . $this->basketId, 'GET', $header );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $this->basketDataCurl['url'], $response ) : '';

        $arrayResponse = json_decode ( $response, true );

        var_dump ( array_keys ( $arrayResponse ) );
    }

    public function generateBasketId () 
    {
        global $VSesiones;

        global $saveLogDB;

        parent :: checkAndConnect ();

        ( empty ( $basketDataCurl ) ) ? parent :: setEnviorentmentProperties ( 'basketDataCurl' ) : '';

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $DBCurl->defaultCurlHeader ( $this->auth, true );

        // "basketId" : "", //Aquí va si tenemos una id previa para añadir más ofertas .

        $postfields = '
        {
            "offerBundleId":"'.$this->offerBundleId.'",

            "selectedOfferIds":["'.implode(',',$this->offersIds).'"]

        }';

        $DBCurl->setProperties ( ['url' => $this->basketDataCurl['url'], 'method' => 'POST', 'postfields' => $postfields] );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        ( $saveLogDB ) ? $DBCurl->insertLog ( $response ) : '';

        $DBCurl->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        $this->basketId = $arrayResponse['basketId'];

        // var_dump ( $arrayResponse['serviceItems'][0]['id'] );
    }

    /**

     * Busca un booking por su id .

     */

    public function getBooking () 
     {
        global $saveLogDB;

        global $VSesiones;

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'getBookingDataCurl' );

        $url = str_replace ( '{bookingId}', $VSesiones["busquedaTR"]['DeutscheBahBookingId'], $dataCurl['url'] );

        $header = $this->defaultCurlHeader ();

        $curlOptions = $this->setCurlOptions ( $url, 'GET', $header );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $dataCurl['url'], $response ) : '';

        $this->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        var_dump ( array_keys ( $arrayResponse ) );
    }

    /**

     * Obtiene la información de un parámetro proporcionada por la propia Api .

     * @param string $parameterName

     */

    public function getMasterData ( string $parameterName ) 
     {
        parent :: checkAndConnect ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'masterDataCurl' );

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $url = $dataCurl['url'] .= '/' . $parameterName;

        $header = [

            "DB-Api-Key:" . $this->auth->getAccessKey ( 'apiKey' ) ,

            "DB-Client-Id:" . $this->auth->getAccessKey ( 'clientId' ) ,

            "accept: application/json",

            "If-None-Match:0a375151f71565456f2fd52c8d1aca9af"

        ];

        $method = 'GET';

        $DBCurl->setProperties ( compact ( 'header', 'url', 'method' ) );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        var_dump ( json_decode ( $response, true ) );
    }

    /**

     * Obtiene las ofertas según consulta .

     * @param int $startStationId Id de la estación origen .

     * @param int $endStationId Id de la estación destino .

     * @param string $triDate Fecha del viaje .

     */

    public function getOffers ( int $startStationId, int $endStationId, string $tripDate ) 
     {
        global $saveLogDB;

        parent :: checkAndConnect ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'offersDataCurl' );

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $DBCurl->defaultCurlHeader ( $this->auth, true );

        $postfields = '
        {
            "passengerData": 
        {
              "passengers": [

                
              {
                  "age": 25,

                  "passengerType": "ERWACHSENER"

                 
                }

              ]

            },

            "comfortClass": "KLASSE_2",

            "tripSearchOutward": 
            {
             "tripSearch": 
            {
               "dateTime": "2022-10-12T00:00:00",

               "originId": "008000105",

               "destinationId": "008000261",

               "products": 9
             }
            }

         }';

        $DBCurl->setProperties ( ['url' => $dataCurl['url'], 'method' => 'POST', 'postfields' => $postfields] );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        ( $saveLogDB ) ? $DBCurl->insertLog ( $response ) : '';

        $arrayResponse = json_decode ( $response, true );

        $DeutscheBahnOffers = new DeutscheBahnOffers ();

        $offerResult = $DeutscheBahnOffers->getOffersContainers ( $arrayResponse['offerBundleContainers'] );

        // echo '<pre>';

        // print_r ( $offerResult );

        // echo '</pre>';

        $this->offerBundleId = $arrayResponse['offerBundleContainers'][0]['offerBundles'][0]['id'];

        $this->offersIds = array_column ( $arrayResponse['offerBundleContainers'][0]['offerBundles'][0]['offers'], 'id' );
    }

    /**

     * Procesa una reserva .

     */

    public function processBooking () 
     {
        global $saveLogDB;

        global $VSesiones;

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'processBookingDataCurl' );

        $url = str_replace ( '{initialBookingId}', $VSesiones["busquedaTR"]['DeutscheBahInitialBookingId'], $dataCurl['url'] );

        $header = $this->defaultCurlHeader ( true );

        $postfields = '

        
        {
          "paymentData": 
        {
            "paymentType": "partnerzahlung",

            "orderer": 
            {
              "firstName": "Erika",

              "lastName": "Musterfrau",

              "email": "maximilian.mustermann@test.de"
            }

          },

          "customers": [

            
          {
              "formOfAddress": "HR",

              "firstName": "Max",

              "lastName": "Mustermann",

              "email": "test@db.de"
            }

          ],

          "ticketHolder": 
          {
            "formOfAddress": "HR",

            "firstName": "Max",

            "lastName": "Mustermann",

            "title": "DR",

            "dateOfBirth": "2000-01-01"

          },

          "basketId": "'.$this->basketId.'"

        }';

        $curlOptions = $this->setCurlOptions ( $url, 'PUT', $header, $postfields );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $dataCurl['url'], $response ) : '';

        $this->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        $VSesiones["busquedaTR"]['DeutscheBahBookingId'] = $arrayResponse['booking']['bookingId'];
    }

    public function refund () 
    {
        global $VSesiones;

        global $saveLogDB;

        ( $this->expiredAuthTime () === true ) ? $this->connectAuth () : '';

        $this->getDatabaseAuth ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'refundDataCurl' );

        $header = $this->defaultCurlHeader ( true );

        $postfields = '
        {
          "bookingId":"'.$VSesiones["busquedaTR"]['DeutscheBahBookingId'].'",

          "cancellationOptionId":"efafd795-f05e-5911-a234-4aa6217a8e3f",

          "email":"maximilian.mustermann@test.de"

        }';

        $curlOptions = $this->setCurlOptions ( $dataCurl['url'], 'POST', $header, $postfields );

        $response = parent :: executeCurl ( $curlOptions );

        ( $saveLogDB ) ? $this->insertLog ( $dataCurl['url'], $response ) : '';

        $this->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        var_dump ( $arrayResponse );
    }

    /**

     * Crea un booking nuevo .

     */

    public function setBooking () 
     {
        global $saveLogDB;

        global $VSesiones;

        parent :: checkAndConnect ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'setBookingDataCurl' );

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $DBCurl->defaultCurlHeader ( $this->auth );

        $DBCurl->setProperties ( ['url' => $dataCurl['url'], 'method' => 'POST'] );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        ( $saveLogDB ) ? $DBCurl->insertLog ( $response ) : '';

        $DBCurl->checkCurlError ( $response );

        $arrayResponse = json_decode ( $response, true );

        $VSesiones["busquedaTR"]['DeutscheBahInitialBookingId'] = $arrayResponse['initialBookingId'];
    }

   /*

    public function getLocations () 
   {
       global $saveLogDB;

       parent :: checkAndConnect ();

       $dataCurl = parent :: setEnviorentmentProperties ( 'locationsDataCurl' );

       $DBCurl = new DeutscheBahnCurl ( $this->name );

       $DBCurl->defaultCurlHeader ( $this->auth );

       $DBCurl->setProperties ( ['url' => $dataCurl['url'] . '?matchValue=Munich', 'method' => 'GET'] );

       $DBCurl->setCurlOptions ();

       $response = $DBCurl->executeCurl ( $curlOptions );

       $DBCurl->checkCurlError ( $response );

       return json_decode ( $response, true );
    }

    public function bestPriceOffer ( int $startStationId, int $endStationId, string $tripDate ) 
    {
        parent :: checkAndConnect ();

        $dataCurl = parent :: setEnviorentmentProperties ( 'bestPriceDataCurl' );

        $DBCurl = new DeutscheBahnCurl ( $this->name );

        $DBCurl->defaultCurlHeader ( $this->auth, true );

        $postfields = '
        {
          "passengerData": 
        {
            "passengers": [

              
            {
                "age": 25,

                "passengerType": "ERWACHSENER",

                "reductions": [

                  
                {
                    "comfortClass": "KLASSE_2",

                    "reductionType": "BAHNCARD50"
                  }

                ]
              }

            ]

          },

          "comfortClass": "KLASSE_2",

          "tripSearchOutward": 
          {
           "tripSearch": 
          {
             "dateTime": "'.$tripDate.'",

             "originId": "'.$startStationId.'",

             "destinationId": "'.$endStationId.'",

             "products": 9
           }
          }

        }';

        $DBCurl->setProperties ( ['url' => $dataCurl['url'], 'method' => 'POST', 'postfields' => $postfields] );

        $DBCurl->setCurlOptions ();

        $response = $DBCurl->executeCurl ();

        $arrayResponse = json_decode ( $response, true );

        var_dump ( $arrayResponse );
    }

    */ 
}
