<?php

namespace Epayco;


use Epayco\Utils\PaycoAes;
use Epayco\Util;
use Epayco\Exceptions\ErrorException;
use WpOrg\Requests\Requests;

/**
 * Client conection api epayco graphql
 */
class GraphqlClient
{
    public function validate($query){
     //Inicializar parametros requeridos para wrapper
     $action = $query->action;
     $selector = isset($query->selector)? $query->selector: null ;
     $selectorOr = isset($query->selectorOr)? $query->selectorOr: null;
     $wildCard = $query->wildCard;
     $byDates =  isset($query->byDates)?$query->byDates: null ;
     $pagination = isset($query->pagination)? $query->pagination: null ;
     $customFields = isset($query->customFields)? $query->customFields: null ;

     //Comprobacion: El query tiene un action: find o findOne?
     if (!($action === "find" || $query->action === "findOne")) {
         throw new ErrorException("Parameter required, please specify action: find or findOne and try again.",102);
     }

     //Comprobacion: El query tiene un atributo selector o selectorOr, si el action es igual a "find" el atributo puede ser nulo, este caso es para listar todos los registros de un modelo
     if (  $selector === null && $selectorOr === null) {
      throw new ErrorException("Parameter required, selector is empty or invalid please fill and try again.",103);
     }else{

      if ($selector !== null && isset( $selector[0])){
          throw new ErrorException("Parameter required, selector is empty or invalid please fill and try again.",103);
      }
     }

    //Comprobacion: El query requiere un comodin de busqueda
    if ($wildCard !== null) {
        if (gettype($wildCard)!== "string") {
          throw new ErrorException("Parameter required, wildCard is empty or invalid please fill and try again.",104);
        }else{
          if (!($wildCard === "contains" || $wildCard === "startsWith")) {
              throw  new ErrorException('Parameter invalid, please specify wildCard: "contains" or "startsWith" and try again.',104);
          }
      }
    }

      //Comprobación: El query solicita rango de fechas de busqueda
    if ($byDates !== null){
        if (gettype($byDates) !== "array"){
            throw  new ErrorException("Parameter required, byDates is empty or invalid please fill and try again.",105);
        }else{
            if (!($this->validateDateFormat($byDates["start"],'YYYY-MM-DD')
                && $this->validateDateFormat($byDates["end"],'YYYY-MM-DD')
                ) ) {
                throw  new ErrorException("Parameter required, byDates is empty or invalid please fill and try again.",105);
            }
        }
    }

      //Comprobación: El query solicita una paginacion de registros
    if ($pagination !== null) {
          if (gettype($pagination) !== "array") {
              throw  new ErrorException("Parameter required, pagination is empty or invalid please fill and try again.",106);
          }else{
              if($pagination["limit"] === null || $pagination["pageNumber"] === null){
                  throw new ErrorException("Parameter required, pagination limit or pageNumber is empty or invalid please fill and try again.",106);
              }else{
                  if (gettype($pagination["limit"]) !== "integer" || gettype($pagination["pageNumber"]) !== "integer") {
                      throw new ErrorException("Parameter required, pagination limit or pageNumber has a invalid value type please fill and try again.",106);
                  }
              }
        }
    }
     //Comprobación: El query solicita campos personalizados
    if ($customFields !== null) {
        if (gettype($customFields) !== "string") {
            throw new ErrorException("Parameter required, customFields is empty or invalid please fill and try again.",107);
        }else{
            if( empty($customFields)){
                throw new ErrorException("Parameter required, customFields is empty or invalid please fill and try again.",107);
            }
        }
    }

    }
    public function sendRequest(string $query, $api_key)
    {
        $headers = [
            "Content-Type: application/json",
            "Accept" => "application/json", 
            "type" => "sdk",
            "authorization" => "Basic " . base64_encode($api_key)
        ];
        try {

            $body = [
                'query' => $query
            ];

            $response = Requests::post(Client::BASE_URL . '/graphql', $headers, $body);

        } catch (\Throwable $th) {
            return $th->getMessage();
        }
        return json_decode($response->body,true);
    }

    public function canPaginateSchema($action,$pagination,$schema){
        if ($pagination !== null) {
            if ($action === "findOne" && $pagination["limit"] !== null) {
                throw  new ErrorException("Can't paginate this schema ${schema}, because this query has only one rows to show, please add a valid query and try again.",108);
            }
        }
    }

    public function paramsBuilder($query){

        $selector = isset($query->selector)? $query->selector: null;
        $selectorOr = isset($query->selectorOr)? $query->selectorOr: null;
        $options = [];

        if ($selector !== null){
            foreach ($selector as $key => $item) {
                $options["selector"] = [
                  "type" => $key,
                  "value" => $item
                ];
            }
            $optionsToJson = json_encode((object)$options);
        }else if ($selectorOr !== null){
            foreach ($selectorOr as $key => $SelectorItem) {
                foreach ($SelectorItem as $key => $item) {
                    $options["selectorOr"][] = [
                        "type" => $key,
                        "value" => $item
                    ];
                }
            }
        }

        return $options;
    }

    public function queryString(
        $selectorParams,
        $schema,
        $query)
    {
        $wildCard = $query->wildCard;
        $byDates =  isset($query->byDates)?$query->byDates: null ;
        $customFields = isset($query->customFields)? $query->customFields: null ;
        $paginationInfo = isset($query->pagination)? $query->pagination: null ;

        $wildCardOption = ($wildCard === null) ? "default": $wildCard;
        $byDatesOptions = ($byDates === null) ? []: $byDates;
        $fields = ($customFields === null) ? $this->fields($schema): $customFields;
        $selectorName = ( empty($selectorParams["selectorOr"]) ) ? "selector":"selectorOr";
        $isPagination = ($paginationInfo === null) ? false:true;

        (object)$queryArgs = [
            "query" => $selectorParams,
            "schema" => $schema,
            "wildCardOption" => $wildCardOption,
            "byDatesOption" => $byDatesOptions,
            "fields" => $fields,
            "selectorName" => $selectorName,
            "paginationInfo" => $paginationInfo
        ];

        return $this->queryTemplates($isPagination,$queryArgs);

    }

    public function fields($type){
        switch ($type){
            case "subscriptions":
                return `_id
        periodStart
        periodEnd
        status
        customer {
          _id
          name
          email
          phone
          doc_type
          doc_number
          cards {
            data {
              token
              lastNumbers
              franquicie
            }
          }
        }
        plan {
          name
          description
          amount
          currency
          interval
          interval_count
          status
          trialDays
        }`;
            case "customer":
                return `name
              _id
              email
              cards {
                token
                data {
                  franquicie
                  lastNumbers
                }
              }
              subscriptions {
                _id
                periodStart
                periodEnd
                status
                plan {
                  _id
                  idClient
                  amount
                  currency
                }
        }`;
            case "customers":
                return `name
              _id
              email
              cards {
                token
                data {
                  franquicie
                  lastNumbers
                }
              }
              subscriptions {
                _id
                periodStart
                periodEnd
                status
                plan {
                  _id
                  idClient
                  amount
                  currency
                }
        }`;
        }

    }

    public function queryTemplates($isPagination, $args){

        $resolverQueryName = "paginated".ucfirst($args["schema"]);
        $selectorName = $args["selectorName"];
        $selectorQuery =  (count($args["query"])>0)?
            preg_replace('/"([^"]+)"\s*:\s*/', '$1:', json_encode($args["query"][$selectorName])): json_encode([]) ;
        $finalQuery = "";
        $byDatesOptions = 'byDate: {}';
        if( count($args["byDatesOption"]) > 0){
            $byDatesOptions = 'byDate: 
                            { 
                                start: "'.$args["byDatesOption"]["start"].'", 
                                end: "'.$args["byDatesOption"]["end"].'" 
                            }';
        }

        switch ($isPagination){
            case true:
                $finalQuery = '
                query getPaginatedRows{
                    '.$resolverQueryName.' (
                        input:{
                            wildCard: "'.$args["wildCardOption"].'"
                            '.$byDatesOptions.'
                            '.$selectorName.': '. $selectorQuery  .'                            
                        }
                        limit:'.$args["paginationInfo"]["limit"].'
                        pageNumber:'.$args["paginationInfo"]["pageNumber"].'   
                    ){
                        totalRows
                        totalRowsByPage
                        '.$args["schema"].'{
                            '.$args["fields"].'
                        }
                        pageInfo {    	
                            hasNextPage
                            actualPage
                            nextPages{
                              page
                            }
                           previousPages{
                              page
                           }
                        }
                    }
                }
                ';
                break;

            case false:
                $finalQuery = ' 
                query '.$args["schema"].' {
                    '.$args["schema"].' (
                      input :{
                        wildCard: "'.$args["wildCardOption"].'"
                        byDate: 
                            { 
                                start: "'.$args["byDatesOption"]["start"].'", 
                                end: "'.$args["byDatesOption"]["end"].'" 
                            }
                        '.$selectorName.': '. $selectorQuery  .'
                      }       
                    ) {
                      '.$args["fields"].'
                    }
                  }
                ';
                break;
        }
        $trimQuery = trim( $finalQuery);
        return $trimQuery;
    }

    public function validateDateFormat($date){
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
            return true;
        } else {
            return false;
        }
    }

    public function successResponse($data,$schema){

        $response = [];
        $isPaginatedResponse = "paginated".ucfirst($schema);

        if( !empty($data["data"][$isPaginatedResponse])){
            $response["success"] = true;
            $response["status"] = true;
            $response["totalRows"]= $data["data"][$isPaginatedResponse]["totalRows"];
            $response["totalRowsByPage"]=  $data["data"][$isPaginatedResponse]["totalRowsByPage"];
            $response["data"] = $data["data"][$isPaginatedResponse][$schema];
            $response["date"]  = date("Y-m-d H:i:sP");
            $response["type"] = 'Find '.$schema;
            $response["object"] = $schema;
            $response["pageInfo"] = [
                "hasNextPage" => $data["data"][$isPaginatedResponse]["pageInfo"]["hasNextPage"],
                "actualPage" => $data["data"][$isPaginatedResponse]["pageInfo"]["actualPage"],
                "nextPages" => $data["data"][$isPaginatedResponse]["pageInfo"]["nextPages"],
                "previousPages" =>  $data["data"][$isPaginatedResponse]["pageInfo"]["previousPages"]
              ];
        }else{
            if ( count($data["data"][$schema]) === 100) { //Objecto de respuesta para cuando la cantidad de registros solicitado supera los 100
                $response["success"] = true;
                $response["status"] = true;
                $response["requirePagination"] = true;
                $response["requirePaginationMessage"] = 'The quantity of rows in result exceeded the max allowed (100), please configure pagination schema and try again ';
                $response["data"] = $data["data"][$schema];
                $response["date"]  = date("Y-m-d H:i:sP");
                $response["type"] = 'Find '.$schema;
                $response["object"] = $schema;
            }else{
                $response["success"] = true;
                $response["status"] = true;
                $response["requirePagination"] = false;
                $response["data"] = $data["data"][$schema];
                $response["date"]  = date("Y-m-d H:i:sP");
                $response["type"] = 'Find '.$schema;
                $response["object"] = $schema;
            }
        }

        return $response;
    }

    
}
