<?php

     /* ElasticSearch Configurations  Starts   */

        $esHost             = '127.0.0.1';
        $esPort             = '9200';
        $esIndex            = 'songs';
        $esType             = 'song';
        $availAggs          = array('Album','Rating');
        $searchQuery        = '';
        $from               = 0;
        $size               = 16;
        $postFilters        = array();
        $result             = '';
        $sortOrder			= 'desc';
		$postSort			= (isset($_REQUEST['sort']))? $_REQUEST['sort'] : 'Rating' ;

		if($postSort == 'Rating'){
		  $sortOrder 		= 'desc';
		}else{
		  $sortOrder 		= 'asc';
		}
     /* ElasticSearch Configurations  Ends   */    
        
     /*
      *     This function used to fire query to Elasticsearch
      * 
      */   
      function call($queryData, $esAPI = '/_search', $method='POST'){
          global $esHost,$esPort,$esIndex,$esType;
	    try {
                $esURL = 'http://'.$esHost.':'.$esPort.'/'.$esIndex.'/'.$esType.$esAPI;
                $ci = curl_init();
                curl_setopt($ci, CURLOPT_URL, $esURL);
                curl_setopt($ci, CURLOPT_PORT, $esPort);
                curl_setopt($ci, CURLOPT_TIMEOUT, 200);
                curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ci, CURLOPT_FORBID_REUSE, 0);
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST,$method);
                curl_setopt($ci, CURLOPT_POSTFIELDS, $queryData);
                return curl_exec($ci);
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\r\n";
            }
      }
          
       // If Any filter applied or search keyword entered
         
          if(isset($_REQUEST['search']) && strlen($_REQUEST['search']) > 2){
            $searchQuery = ' "query": {
                                       
                                    
                                "bool": {
                                  "should": [
                                    { 
                                        "multi_match" : {
                                            "query":      "' . $_REQUEST['search'] . '",
                                            "type":       "most_fields",
                                            "fields":     [ "Title.analyzed", "Album.analyzed"],
                                            "operator": "and",
                                            "tie_breaker": 0.3
                                          } 
                                    },
                                    {
                                      "term": { "Album":  "' . $_REQUEST['search'] . '"  }
                                    }
                                    
                                  ],
                                  "minimum_number_should_match": 1
                                }
                              },
                        ';
            
          }
      
      if(isset($_REQUEST['search']) &&  isset($_REQUEST['filters'])){
          $mainQuery    = '';
    
          $mainFilter   = buildFilter($_REQUEST['filters']);
          $mainAggs     = buildAggregation($_REQUEST['filters']);
		  $postFilters  = $_REQUEST['filters'];
          $mainQuery    = '{ '.$searchQuery.$mainFilter.','.$mainAggs.', "from":'.$from.', "size":'.$size.', "sort" : [ {"'.$postSort.'" : {"order" : "'.$sortOrder.'"}} ]}';
          $result 		= call($mainQuery);
          $result 		= json_decode($result, true);
          
      }else {
          //  ElasticSearch Query without Any filters.      
      
                $mainQuery    =  '
                        "aggs": {
                           "Album": {
                              "filter": {},
                              "aggs": {
                                 "Album": {
                                    "terms": {
                                       "field": "Album",
                                       "size": 200
                                    }
                                 }
                              }
                           },
                           "Rating": {
                              "filter": {},
                              "aggs": {
                                 "Rating": {
                                    "terms": {
                                       "field": "Rating",
                                       "size": 200
                                    }
                                 }
                              }
                           }
                        }
                     ';
                
          $mainQuery    = '{ '.$searchQuery.''.$mainQuery.', "from":'.$from.', "size":'.$size.', "sort" : [ {"'.$postSort.'" : {"order" : "'.$sortOrder.'"}} ] }';      
          $result = call($mainQuery);
          
          $result = json_decode($result, true);    
      }
      
      /*
       * Building Filter query 
       * 
       */
    function buildFilter($filters = array(), $exclude = ''){
        
        if(is_array($filters) && count($filters) > 0){
            $filterQuery = '';
            foreach($filters as $index => $value){
                if($index != $exclude){
                   
                    $filterQuery .= setTerm($index,$value);
                    
                } // End If for filter condition check
                
            } // End Foreach for user submitted filters
            
            if($filterQuery == ''){
                
                return '"filter": { }';
                
            }else {
                $filterQuery = '"filter": {
                                    "and": {
                                      "filters": ['.rtrim($filterQuery, ",") . '] } }';
                return $filterQuery;
            }
            
            
        }else {
            return '"filter": { }';
        } // End If check for filters query string
        
        return $filterQuery;
    } // End buildFilter Function
      
    /*
     * Set Term Condition for individual Fields. 
     * If filter has more than one value its OR nested condition
     * 
     */
   function setTerm($index,$value){
       $filterQuery = '';
       if(is_array($value) && count($value) > 1){
           $orFilterQuery = '';
                       foreach($value as $orValue){
                            $orFilterQuery .= '{
                                                "term": {
                                                   "' . $index . '": "' . $orValue . '"
                                                 }
                                                },';
                        }
                        $filterQuery .= '{"or": ['.rtrim($orFilterQuery, ",") . '] },';
                       
                   }else {
                        $filterQuery .= '{
                                                "term": {
                                                   "' . $index . '": "' . $value[0] . '"
                                                 }
                                          },';
                   } // End If for AND or OR operator selection
       return $filterQuery;            
   } 
   

   
   /*
    *  Build Aggregation with filters. 
    *  Aggregation filter needs to be exclude own field filters. 
    * 
    */
   
   function buildAggregation($formFilters = array()){
       global $availAggs; 
       $formFiltersIndex = array_keys($formFilters);
       $aggsQuery = '';
       foreach($availAggs as $value){
           $excludeFilter = '';
           if(in_array($value, $formFiltersIndex)){
              $excludeFilter = $value; 
           }
               
               $aggFilter = buildFilter($formFilters,$excludeFilter);
               $aggsQuery .= '"'.$value.'": {
                                '.$aggFilter.',
                                "aggs": {
                                   "'.$value.'": {
                                      "terms": {
                                         "field": "'.$value.'",
                                         "size": 200
                                      }
                                   }
                                }
                             },';
       } // End Foreach Aggregation loop
       
       $aggsQuery = '"aggs": {'.rtrim($aggsQuery, ",") . '}';
       return $aggsQuery;
   }
   //header('Content-Type: application/json');
   echo json_encode($result);
  