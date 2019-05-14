<?php
//. phpQuery
require_once('./phpQuery-onefile.php');

//. generate city list
$fp = fopen( '000618153.csv', 'r' );
$citys = array();
while( ( $line = fgets( $fp ) ) != NULL ){
  //echo $line;
  $tmp = explode( ',', $line );
  if( $tmp[2] ){
    array_pop( $tmp );
    array_pop( $tmp );
    array_push( $citys, $tmp );  //. 0:code, 1:pref, 2:city, 3:pref_ruby, 4:city_ruby
  }
}
fclose( $fp );

//. base URL
$base_url = 'https://ja.wikipedia.org';

//. output file
$filename = 'result.tsv';
file_put_contents( $filename, '' );

//. first page  https://ja.wikipedia.org/wiki/市町村章
$page_url = $base_url . '/wiki/%E5%B8%82%E7%94%BA%E6%9D%91%E7%AB%A0';

//. analyze first page
analyze_page0( $page_url );

//. analyze first page
function analyze_page0( $url ){
  global $base_url;

  //. retrieve webpage
  $html = file_get_contents($url);

  //. find element using DOM
  $doc = phpQuery::newDocument($html);
  $trs = $doc["table.collapsible tbody tr"];
  foreach( $trs as $tr ){
    $area_name = pq($tr)->find("td:eq(0)")->text();
    $pref_as = pq($tr)->find("td:eq(1)")->find("a");
    foreach( $pref_as as $pref_a ){
      $pref_name = pq($pref_a)->text();
      $pref_href = pq($pref_a)->attr("href");
      //echo $area_name . " : " . $pref_name . "(" . $pref_href . ")\n";

      analyze_page1( $area_name, $pref_name, $base_url . $pref_href );
    }
  }
}

//. analyze pref page
function analyze_page1( $area, $pref, $url ){
  global $base_url;

  //. retrieve webpage
  $html = file_get_contents($url);

  //. find element using DOM
  $doc = phpQuery::newDocument($html);
  $tables = $doc["table.wikitable"];
  $county_name = '';
  foreach( $tables as $table ){
    $trs = pq($table)->find("tbody tr");
    $ths = pq($table)->find("tbody tr th");
    //echo count($ths) . "\n";  //. 5, 6, or 7
    $th_num = count($ths);
    $tr_of_th = null;
    foreach( $trs as $tr ){
      $tds = pq($tr)->find("td");
      $td_num = count($tds);
      if( $td_num == 0 ){
        $tr_of_th = $tr;
      }else{
        $city_name = $city_url = $city_img_url = $body = $year = '';
        switch( $th_num ){
        case 5:  //. 市区, 市区章, 由来, 制定日, 備考
          $city_name = pq($tr)->find("td:eq(0)")->text();
          $city_url = pq($tr)->find("td:eq(0) a")->attr("href");
          $city_img_url = pq($tr)->find("td:eq(1) a img")->attr("src");
          $body = pq($tr)->find("td:eq(2)")->text();
          $year = pq($tr)->find("td:eq(3)")->text();
          break;
        case 6:  //. 群, 町村, 町村章, 由来, 制定日, 備考
                 //. 市区, 市区章, 由来, 制定日, 廃止日, 備考??
          $tmp4 = pq($tr_of_th)->find("th:eq(4)")->text();
          if( $tmp4 == '廃止日' ){
            $city_name = pq($tr)->find("td:eq(0)")->text();
            $city_url = pq($tr)->find("td:eq(0) a")->attr("href");
            $city_img_url = pq($tr)->find("td:eq(1) a img")->attr("src");
            $body = pq($tr)->find("td:eq(2)")->text();
            $year = pq($tr)->find("td:eq(3)")->text();
          }else{
            $tmp0 = pq($tr_of_th)->find("th:eq(0)")->text();
            if( strpos( $tmp0, '郡' ) !== false ){
              $county_name = pq($tr)->find("td:eq(0)")->text();
              $city_name = pq($tr)->find("td:eq(1)")->text();
              $city_url = pq($tr)->find("td:eq(1) a")->attr("href");
              $city_img_url = pq($tr)->find("td:eq(2) a img")->attr("src");
              $body = pq($tr)->find("td:eq(3)")->text();
              $year = pq($tr)->find("td:eq(4)")->text();
            }else{
              $city_name = pq($tr)->find("td:eq(0)")->text();
              $city_url = pq($tr)->find("td:eq(0) a")->attr("href");
              $city_img_url = pq($tr)->find("td:eq(1) a img")->attr("src");
              $body = pq($tr)->find("td:eq(2)")->text();
              $year = pq($tr)->find("td:eq(3)")->text();
            }
          }

          break;
        case 7:  //. 群, 町村, 町村章, 由来, 制定日, 廃止日, 備考
          $tmp0 = pq($tr_of_th)->find("th:eq(0)")->text();
          if( strpos( $tmp0, '郡' ) !== false ){
            $county_name = pq($tr)->find("td:eq(0)")->text();
            $city_name = pq($tr)->find("td:eq(1)")->text();
            $city_url = pq($tr)->find("td:eq(1) a")->attr("href");
            $city_img_url = pq($tr)->find("td:eq(2) a img")->attr("src");
            $body = pq($tr)->find("td:eq(3)")->text();
            $year = pq($tr)->find("td:eq(4)")->text();
          }else{
            $city_name = pq($tr)->find("td:eq(0)")->text();
            $city_url = pq($tr)->find("td:eq(0) a")->attr("href");
            $city_img_url = pq($tr)->find("td:eq(1) a img")->attr("src");
            $body = pq($tr)->find("td:eq(2)")->text();
            $year = pq($tr)->find("td:eq(3)")->text();
          }
          break;
        }

        if( $city_name && $city_img_url ){
          analyze_page2( trim($area), trim($pref), trim($county_name), trim($city_name), trim($body), trim($year), trim($city_img_url), trim($city_url) );
        }
      }
    }
  }
}

function analyze_page2( $area, $pref, $county, $city, $body, $year, $url, $city_url ){
  global $base_url;
  global $citys;
  global $filename;

  //array_push( $citys, $tmp );  //. 0:code, 1:pref, 2:city, 3:pref_ruby, 4:city_ruby
  $n = count($citys);
  $code = $pref_ruby = $city_ruby = '';
  for( $i = 0; $i < $n && !$code;  $i ++ ){
    $c = $citys[$i];
    if( $c[1] == $pref && $c[2] == $city ){
      $code = $c[0];
      $pref_ruby = $c[3];
      $city_ruby = $c[4];
    }
  }

  //. find city detail
  $lat = $lng = '';
  if( $city_url ){
    $html = file_get_contents($base_url . $city_url);
    $doc = phpQuery::newDocument($html);
    $a = $doc["span[title='この位置の地図や航空写真などをリンクするページを表示します']"]->parent();
    $href = pq($a)->attr('href');

    $params = explode( '&', $href );
    foreach( $params as $param ){
      $kv = explode( '=', $param );
      if( count($kv) == 2 && $kv[0] == 'params' ){
        //. $kv[1] = '35_54_5.8_N_140_24_19_E_region:JP';
        $tmp1 = explode( '_N_', $kv[1] );
        $tmp2 = explode( '_E_', $tmp1[1] );

        $lat = replaceToFloat( $tmp1[0] );
        $lng = replaceToFloat( $tmp2[0] );
      }
    }
  }

  //. $code が入っていないケース＝廃止、合併で吸収された、など
  $line = $code . "\t" . $area . "\t" . $pref . "\t" . $pref_ruby . "\t" . $county . "\t" . $city . "\t" . $city_ruby . "\t" . myTrim($year) . "\t" . myTrim($body) . "\t" . $lat . "\t" . $lng . "\t" . $url . "\n";
  file_put_contents( $filename, $line, FILE_APPEND | LOCK_EX );
}

function replaceToFloat( $ss ){  //. 'NN_NN_N.N'
  $ss = str_replace( '.', '', $ss );
  $tmp = explode( '_', $ss );

  $r = $tmp[0] . "." . $tmp[1] . $tmp[2];
  return floatval($r);
}

function myTrim( $str ){
  return preg_replace( "/\[\d+\]/", "", $str );
}
 ?>
