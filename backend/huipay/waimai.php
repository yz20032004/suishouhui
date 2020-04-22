<?php
  require_once dirname(__FILE__).'/../common.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_order_url':
      $mchId = $_GET['mch_id'];
      $openId= $_GET['openid'];
      $sql = "SELECT is_open, formid, delivery_time_end FROM mch_waimai_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if (!$row['is_open']) {
        echo 'fail';
      //} else if (time() > strtotime(date('Y-m-d '.$row['delivery_time_end'].':00'))) {
      //  echo 'close';
      } else {
        $formId = $row['formid'];

        $orderExt = $mchId.'#'.$openId;
        $key      = substr(md5($orderExt), 8, 8);
        $redis->hset('keyou_waimai_orders', $key, $orderExt);
        $url = 'https://biaodan.info/web/formview/'.$formId.'?ex='.$key;
        echo $url;
      }
      break;
    case 'get_distance':
      $shopLatitude = $_GET['shop_latitude'];
      $shopLongitude= $_GET['shop_longitude'];
      $userLatitude = $_GET['user_latitude'];
      $userLongitude= $_GET['user_longitude'];

      $distance = getDistance($shopLatitude, $shopLongitude, $userLatitude, $userLongitude);
      echo $distance;
      break;
    case 'get_order':
      $mchId   = $_GET['mch_id'];
      $openId  = $_GET['openid'];

      $orderExt = $mchId.'#'.$openId;
      $key      = substr(md5($orderExt), 8, 8);

      $sql = "SELECT * FROM mch_waimai_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $formId = $row['formid'];
      $deliveryDistance = $row['delivery_distance'];
      $costAtLeast = $row['cost_atleast'];
      $deliveryCost= $row['delivery_cost'];
      $packageCost = $row['package_cost'];
      $deliveryFreeAtLeast = $row['delivery_free_atleast'];
      $canRecharge = $row['can_recharge'];

      $cmd = 'curl -u '.JSFORM_APP_KEY.':'.JSFORM_APP_SECRET.' --header "Content-Type:application/json" -d';
      $queryData = array(
                    'form_id' => $formId,
                    'order_by'=> array('create_time' => -1),
                    'filters' => array(
                                    array(
                                      'field' => 'ext_value',
                                      'compare_type' => 'eq',
                                      'data_type' => 'string',
                                      'value'     => $key
                                    )
                                 )
                   );
      $queryString = addslashes(json_encode($queryData));
      $cmd .= ' "'.$queryString.'" --url http://api.jsform.com/api/v1/entry/query';
      file_put_contents('/tmp/mchsql', $cmd.PHP_EOL, FILE_APPEND);
      exec($cmd, $output);
      $result = json_decode($output[0], true);
      $orderAmount = $result['rows'][0]['amount'];
      
      $goodTotal = 0;
      foreach ($result['rows'][0] as $key=>$value) {
        if (strstr($key, 'field') && is_numeric($value)) {
          $goodTotal += $value;
        }
      }
      $deliveryCost = $deliveryFreeAtLeast != '0' && $orderAmount >= $deliveryFreeAtLeast ? 0 : $deliveryCost;
      $packageCostTotal = $goodTotal * $packageCost;
      $costTotal        = $orderAmount + $deliveryCost + $packageCostTotal;

      $row['order_amount']  = $orderAmount;
      $row['delivery_cost'] = $deliveryCost;
      $row['package_cost']  = $packageCostTotal;
      $row['cost_total']    = $costTotal;

      $subscribeDeliveryTimes = $subscribeDeliveryTotalDayTimes = array();
      $startTime = strtotime(date('Y-m-d '.$row['delivery_time_start'].':00'));
      $endTime = strtotime(date('Y-m-d '.$row['delivery_time_end'].':00'));
      $deliveryArrivedTime = $startTime + ($row['delivery_time'] * 60);
      for($i=$deliveryArrivedTime;$i<=$endTime;$i+=60) {
        if (date('i', $i) == '00' || date('i', $i) == '30') {
          $subscribeDeliveryTotalDayTimes[] = date('H:i', $i);
        }
        if ($i < time() + $row['delivery_time'] * 60) {
          continue;
        }
        if (date('i', $i) == '00' || date('i', $i) == '30') {
          $subscribeDeliveryTimes[] = date('H:i', $i);
        }
      }
      $row['subscribe_delivery_times'] = $subscribeDeliveryTimes ? $subscribeDeliveryTimes : $subscribeDeliveryTotalDayTimes;
      $row['subscribe_delivery_totalday_times'] = $subscribeDeliveryTotalDayTimes;
      if ($subscribeDeliveryTimes) {
        $days = array('今日');
      }
      for($i=1;$i<5;$i++){
        $days[] = date('m月d日', strtotime('+'.$i.' days'));
      }
      $row['subscribe_delivery_days'] = $days;
      $row['can_immediate'] = time() >= $startTime && time() < $endTime ? true : false;
      echo json_encode($row);
      break;
    case 'update_remind':
      $outTradeNo     = $_GET['out_trade_no'];
      $remindFunction = $_GET['remind_function'];
      $isRemind       = $_GET['is_remind'];
      $sql = "UPDATE member_waimai_orders SET $remindFunction = $isRemind WHERE out_trade_no = '$outTradeNo'";
      $db->query($sql);
      echo 'success';
      break;
    default:
      break;
  }

  /**
*  @desc 根据两点间的经纬度计算距离
*  @param float $lat 纬度值
*  @param float $lng 经度值
*/
 function getDistance($lat1, $lng1, $lat2, $lng2)
 {
     $earthRadius = 6367000; //approximate radius of earth in meters
     /*
       Convert these degrees to radians
       to work with the formula
     */
     $lat1 = ($lat1 * pi() ) / 180;
     $lng1 = ($lng1 * pi() ) / 180;

     $lat2 = ($lat2 * pi() ) / 180;
     $lng2 = ($lng2 * pi() ) / 180;

     /*
       Using the
       Haversine formula
       http://en.wikipedia.org/wiki/Haversine_formula
       calculate the distance
     */

     $calcLongitude = $lng2 - $lng1;
     $calcLatitude = $lat2 - $lat1;
     $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);  $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
     $calculatedDistance = $earthRadius * $stepTwo;

     return round($calculatedDistance/1000, 1);
 }
