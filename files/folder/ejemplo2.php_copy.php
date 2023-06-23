

<?php

namespace App\Libraries\DeutscheBahn;







class DeutscheBahnOffers{

    /**

     * Obtiene la información general sobre la consulta de una oferta de viaje.

     * @param array $offersBundlesContainers Lista de "contenedores" de ofertas.

     * @see DeutscheBahn::getOffers

     * @return array 

     */

    public function getOffersContainers(array $offersBundlesContainers){

        $bundlesResult=[];

        $aux_bundle=0;

        foreach($offersBundlesContainers as $container){

          $inwardTrip=(array_key_exists('inwardTrip',$container))?$this->getTripData($container['outwardTrip']):[];

          $outwardTrip=$this->getTripData($container['outwardTrip']);

          $offersBundle=$this->getOffersBundles($container['offerBundles']);

          $addContainer=compact('inwardTrip', 'outwardTrip','offersBundle');

          array_push($bundlesResult,$addContainer);

        }

        return $bundlesResult;

    }

    /**

     * Busca la información relacionada con el viaje de ida y de vuelta.

     * @param array $trip

     * @see DeutscheBahnOffers::getOffersContainers

     * @return array

     */

    private function getTripData(array $trip){

       $tripResult=['id'=>$trip['id'],'duration'=>$trip['duration'],'segments'=>[]];

       if(array_key_exists('segments',$trip) and !empty($trip['segments'])){

          foreach($trip['segments'] as $segment){

            $addSegment=[

               'originId'=>$segment['origin']['id'],

               'originName'=>$segment['origin']['name'],

               'originDateTime'=>$segment['origin']['dateTime'],

               'destinationId'=>$segment['destination']['id'],

               'destinationDateTime'=>$segment['destination']['dateTime'],

            ];

            if(array_key_exists('productDetails',$segment)){

               $addSegment['trainType']=$segment['productDetails']['catOutL'];

               $addSegment['trainName']=$segment['productDetails']['name'];

            }

            array_push($tripResult['segments'],$addSegment);

          }

       }

       return $tripResult;

    }

    /**

     * Repasa los paquetes de ofertas para obtenerlas.

     * @param array $bundles Paquetes de ofertas.

     * @see DeutscheBahnOffers::getOffersContainers

     * @return array

     */

    private function getOffersBundles(array $bundles){

        $bundlesResult=[];

        if(!empty($bundles)){

          foreach($bundles as $bundle){

            $extractedOffers=$this->getOffers($bundle['offers']);

            $offerBundles=$this->mountOffersRelations($extractedOffers,$bundle['offerRelations'][0]);

            array_push($bundlesResult,$offerBundles);

          }

        }

        return $bundlesResult;

    }

    /**

     * Toda la información de cada una de las ofertas.

     * @param array $offersData Lista de ofertas donde sacar la información.

     * @see DeutscheBahnOffers::getOffersBundles

     * @return array

     */

    private function getOffers(array $offersData){

        $offersResult=[];

        foreach($offersData as $offer){

            $fulfillment=$offer['fulfillmentInfo']['passengerProfiles'][0];

            $addOffer=[

              'id'=>$offer['id'],

              'class'=>$offer['validityInfos'][0]['comfortClass'],

              // 'productCategory'=>$offer['validityInfos'][0]['productCategory'],

              'validityPeriod'=>$offer['validityInfos'][0]['validityPeriod'],

              'numberOfPassengers'=>$fulfillment['numberOfPassengers'],

              'passengerType'=>$fulfillment['passengerType'],

              'ageOfPassengers'=>$fulfillment['ageOfPassengers'],

              'reductions'=>[],

              'price'=>[

                'amount'=>$offer['price']['amount'],

                'currency'=>$offer['price']['currency']

              ],

              'afterSalesConditions'=>$this->getSalesConditions($offer['afterSalesConditions'])

            ];

            $addOffer['reductions']=(array_key_exists('reductionsApplied',$fulfillment) and !empty($fulfillment['reductionsApplied'][0]))?$fulfillment['reductionsApplied'][0]:$offer['reductions'];

            array_push($offersResult,$addOffer);

        }

        return $offersResult;

    }

    /**

     * Las condiciones post-venta de cada oferta. 

     * @param array $conditions 

     * @see DeutscheBahnOffers::getOffers

     * @return array

     */

    private function getSalesConditions(array $conditions){

        $conditionsResult=[];

        if(!empty($conditions)){

            foreach($conditions as $condition){

                $addCondition=[

                    'id'=>$condition['id'],

                    'afterSalesFeeId'=>$condition['afterSalesFee']['chargeId'],

                    'afterSalesFeePrice'=>$condition['afterSalesFee']['feePrice'],

                    'validFrom'=>$condition['validFrom'],

                    'validTo'=>$condition['validTo'],

                    'type'=>$condition['afterSalesType'],

                ];

                if(array_key_exists('validAsOfBooking',$conditionsResult)){

                  $addCondition['validAsOfBooking']=$condition['validAsOfBooking'];

                }

                array_push($conditionsResult,$addCondition);

            }

        }

        return $conditionsResult;

    } 

    /**

     * Gestiona las relaciones que hay entre las ofertas de un "bundle".

     * @param array $offers 

     * @param array $relations Relaciones entre ofertas.

     * @see DeutscheBahnOffers::getOffersBundles

     * @return array

     */

    private function mountOffersRelations(array $offers, array $relations){

        $offerResult=[];

        if(!empty($relations) and !empty($offers)){

           $offersIds=array_column($offers,'id');

           $position=array_search($relations['offerId'],$offersIds);

           $offerResult=$offers[$position];

           if(array_key_exists('references',$relations) and array_key_exists(0,$relations['references']) and !empty($relations['references'][0])){

              $offerResult=$this->mountOffersReference($offers,$relations['references'][0],$offerResult,$offersIds);

           }

        }

       return $offerResult;

    }

    /**

     * Adhiere cada una de las ofertas con las que están relacionadas.

     * @param array $offers Información de las ofertas.

     * @param array $references Relaciones entre ofertas.

     * @param array $offerResult Resultado del relación entre las ofertas. 

     * @param array $offerIds (Opcional)

     * @see DeutscheBahnOffers::mountOffersRelations

     * @return array

     */

    private function mountOffersReference(array $offers, array $references, array $offerResult,array $offersIds=[]){

      $offersIds=(empty($offersIds))?array_column($offers,'id'):$offersIds;

      $position=array_search($references['referencedOffers'][0]['offerId'],$offersIds);

      $addOffer=(is_int($position))?$offers[$position]:[];

      if(!empty($addOffer) and array_key_exists('references',$references['referencedOffers'][0]) and array_key_exists(0,$references['referencedOffers'][0]['references']) and !empty($references['referencedOffers'][0]['references'][0])){

        $addOffer=$this->mountOffersReference($offers,$references['referencedOffers'][0]['references'][0],$addOffer,$offersIds);

      }

      $offerResult['offersRelations']=[

        'relationType'=>$references['relationType'],

        'relationOperand'=>$references['referenceSelection']['relationOperand'],

        'offerRelation'=>$addOffer

      ];

      return $offerResult;

    }

}