<?php
$fp = fopen( '000618153.csv', 'r' );

$citys = array();
while( ( $line = fgets( $fp ) ) != NULL ){
  //echo $line;
  $tmp = explode( ',', $line );
  if( $tmp[2] ){
    array_pop( $tmp );
    array_pop( $tmp );
    array_push( $citys, $tmp );
  }
}
print_r( $citys );
 ?>
