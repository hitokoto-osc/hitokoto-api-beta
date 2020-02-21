<?php
$stime=microtime(true);
header('Access-Control-Allow-Origin:*');
header('Time:'.time());
header('Cache-Control: no-cache');
header('Content-Type:text/html; charset=utf-8');


$memcache_obj = new Memcache; 
$conn_status=$memcache_obj->connect('127.0.0.1', 11211); 
if(!$conn_status){
    exit("[ERROR]Code:-1MemcacheConnectError");
}
// if($_GET['test']==1){


// }
if($_GET['count']==1){
    exit($memcache_obj->get('hitokoto_233'));
}
if(time()-$memcache_obj->get('hitokoto_time')>3600){
    $memcache_obj->set('hitokoto_status','2', MEMCACHE_COMPRESSED, 0);
}
if($var = $memcache_obj->get('hitokoto_status')!=1){
    $memcache_obj->set('hitokoto_time',time(), MEMCACHE_COMPRESSED, 0);
    //初始化
    $db = new PDO ("mysql:host=127.0.0.1;dbname=hitokoto",'root','hitokoto!');
    $rs = $db->query('SELECT * FROM `hitokoto_sentence`');
    $i = 1;
    
    while($row = $rs -> fetch()){
        $memcache_obj->set('hitokoto_text_'.$i, $row['hitokoto'], MEMCACHE_COMPRESSED, 0);
        $json['id']=$row['id'];
        $json['hitokoto']=$row['hitokoto'];
        $json['type']=$row['type'];
        $json['from']=$row['from'];
        $json['creator']=$row['creator'];
        $json['created_at']=$row['created_at'];
        $memcache_obj->set('hitokoto_text_'.$i."_json", json_encode($json), MEMCACHE_COMPRESSED, 0);
        unset($json);
        $i++;
    }
    $memcache_obj->set('hitokoto_number', $i, MEMCACHE_COMPRESSED, 0); 
    
    $cat=array('a', 'b', 'c', 'd', 'e', 'f', 'g');
    $i=0;
    for ($i = 0; $i < count($cat); $i++) {
        $rs = $db->query("SELECT * FROM `hitokoto_sentence` WHERE `type` = '".$cat[$i]."'");
        $ii = 1;
        while($row = $rs -> fetch()){
            $memcache_obj->set('hitokoto_text_'.$cat[$i].'_'.$ii, $row['hitokoto'], MEMCACHE_COMPRESSED, 0);
            $json['id']=$row['id'];
            $json['hitokoto']=$row['hitokoto'];
            $json['type']=$row['type'];
            $json['from']=$row['from'];
            $json['creator']=$row['creator'];
            $json['created_at']=$row['created_at'];
            $memcache_obj->set('hitokoto_text_'.$cat[$i].'_'.$ii.'_json', json_encode($json), MEMCACHE_COMPRESSED, 0);
            unset($json);
            $ii++;
        }
        $memcache_obj->set('hitokoto_number_'.$cat[$i], $ii, MEMCACHE_COMPRESSED, 0);
    }
    $memcache_obj->set('hitokoto_status', '1', MEMCACHE_COMPRESSED, 0); 
    header("Reload:OK");
    
}else{
    header("Reload:None");
}
//初始化完成或者已经在内存
$num = $memcache_obj->get('hitokoto_233');
header("Num:$num");
$memcache_obj->set('hitokoto_233', $num+1, MEMCACHE_COMPRESSED, 0);
$num=0;

$num = $memcache_obj->get('hitokoto_number_'.$_GET['c']); 

if(!$num){
    //不存在的cat类别 从全部中随机
    $num = $memcache_obj->get('hitokoto_number'); 
    if($_GET['encode'] === 'text'){
		if($_GET['limit']){
			$text = $memcache_obj->get('hitokoto_text_'.rand(1,$num));
			if(mb_strlen($text,'utf-8') > $_GET['limit']){
				$text = mb_substr($text,0,$_GET['limit'],'utf-8')."...";
			}
		}else{
			$text = $memcache_obj->get('hitokoto_text_'.rand(1,$num));
		}
        //$text=$_GET['limit'] ? mb_substr($memcache_obj->get('hitokoto_text_'.rand(1,$num)),0,$_GET['limit'],'utf-8')."..." : $memcache_obj->get('hitokoto_text_'.rand(1,$num));
    }else if($_GET['encode'] === 'json'){//返回不转码的JSON
		if($_GET['limit']){
            $text_json=json_decode($memcache_obj->get('hitokoto_text_'.rand(1,$num)."_json"));
			if(mb_strlen($text_json->hitokoto,'utf-8') > $_GET['limit']){
				$text_json->hitokoto=mb_substr($text_json->hitokoto,0,$_GET['limit'],'utf-8')."...";
			}
            $text=json_encode($text_json,JSON_UNESCAPED_UNICODE);
        }else{
            $text=$memcache_obj->get('hitokoto_text_'.rand(1,$num)."_json");
			$text_json=json_decode($text);
			$text=json_encode($text_json,JSON_UNESCAPED_UNICODE);
        }
	}else{
        if($_GET['limit']){
            $text_json=json_decode($memcache_obj->get('hitokoto_text_'.rand(1,$num)."_json"));
			if(mb_strlen($text_json->hitokoto,'utf-8') > $_GET['limit']){
				$text_json->hitokoto=mb_substr($text_json->hitokoto,0,$_GET['limit'],'utf-8')."...";
			}
            $text=json_encode($text_json);
        }else{
            $text=$memcache_obj->get('hitokoto_text_'.rand(1,$num)."_json");
        }
    }
}else{
    
    if($_GET['encode'] === 'text'){
		if($_GET['limit']){
			$text = $memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num));
			if(mb_strlen($text,'utf-8') > $_GET['limit']){
				$text = mb_substr($text,0,$_GET['limit'],'utf-8')."..." ;
			}
			//$text = mb_substr($memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)),0,$_GET['limit'],'utf-8')."..." ;
		}else{
			$text = $memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num));
		}
        //$text=$_GET['limit'] ? mb_substr($memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)),0,$_GET['limit'],'utf-8')."..." : $memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num));
    }else if($_GET['encode'] === 'json'){//返回不转码的JSON
		if($_GET['limit']){
            $text_json=json_decode($memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)."_json"));
			if(mb_strlen($text_json->hitokoto,'utf-8') > $_GET['limit']){
				$text_json->hitokoto=mb_substr($text_json->hitokoto,0,$_GET['limit'],'utf-8')."...";
			}
            $text=json_encode($text_json,JSON_UNESCAPED_UNICODE);
        }else{
            $text=$memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)."_json");
			$text_json=json_decode($text);
			$text=json_encode($text_json,JSON_UNESCAPED_UNICODE);
        }
	}else{
        if($_GET['limit']){
            $text_json=json_decode($memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)."_json"));
			if(mb_strlen($text_json->hitokoto,'utf-8') > $_GET['limit']){
				$text_json->hitokoto=mb_substr($text_json->hitokoto,0,$_GET['limit'],'utf-8')."...";
			}
            $text=json_encode($text_json);
        }else{
            $text=$memcache_obj->get('hitokoto_text_'.$_GET['c']."_".rand(1,$num)."_json");
        }
        //$text=$memcache_obj->get('hitokoto_text_'.rand(1,$num)."_json");
    }

}

$etime=microtime(true);//获取程序执行结束的时间
$total=$etime-$stime;   //计算差值
header("RunTime:".$total) ;
echo $text;
exit();

