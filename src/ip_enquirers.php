<?php
// 获取用户IP地址
function getIp() {
    $onlineip = '';
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $onlineip = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $onlineip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $onlineip = getenv('REMOTE_ADDR');
    }
    elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $onlineip = $_SERVER['REMOTE_ADDR'];
    }
    preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
    $onlineip = $onlineipmatches[0] ? $onlineipmatches[0] : '0.0.0.0';
    
    return $onlineip;
}
// 函数conrvertip()位于 Discuz!的/upload/include/misc.func.php 路径中
function convertIp($ip) {
    $return = '';
    if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {

        $iparray = explode('.', $ip);

        if($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
            $return = 'LAN';
        } elseif($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
            $return = 'Invalid IP Address';
        } else {
            $ipdatafile = 'qqwry.dat';
            if(!$fd = @fopen($ipdatafile, 'rb')) {
                return 'Invalid IP data file';
            }

            $ip = explode('.', $ip);
            $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

            if(!($DataBegin = fread($fd, 4)) || !($DataEnd = fread($fd, 4)) ) return;
            @$ipbegin = implode('', unpack('L', $DataBegin));
            if($ipbegin < 0) $ipbegin += pow(2, 32);
            @$ipend = implode('', unpack('L', $DataEnd));
            if($ipend < 0) $ipend += pow(2, 32);
            $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

            $BeginNum = $ip2num = $ip1num = 0;
            $ipAddr1 = $ipAddr2 = '';
            $EndNum = $ipAllNum;

            while($ip1num > $ipNum || $ip2num < $ipNum) {
                $Middle= intval(($EndNum + $BeginNum) / 2);

                fseek($fd, $ipbegin + 7 * $Middle);
                $ipData1 = fread($fd, 4);
                if(strlen($ipData1) < 4) {
                    fclose($fd);
                    return 'System Error';
                }
                $ip1num = implode('', unpack('L', $ipData1));
                if($ip1num < 0) $ip1num += pow(2, 32);

                if($ip1num > $ipNum) {
                    $EndNum = $Middle;
                    continue;
                }

                $DataSeek = fread($fd, 3);
                if(strlen($DataSeek) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
                fseek($fd, $DataSeek);
                $ipData2 = fread($fd, 4);
                if(strlen($ipData2) < 4) {
                    fclose($fd);
                    return 'System Error';
                }
                $ip2num = implode('', unpack('L', $ipData2));
                if($ip2num < 0) $ip2num += pow(2, 32);

                if($ip2num < $ipNum) {
                    if($Middle == $BeginNum) {
                        fclose($fd);
                        return 'Unknown';
                    }
                    $BeginNum = $Middle;
                }
            }

            $ipFlag = fread($fd, 1);
            if($ipFlag == chr(1)) {
                $ipSeek = fread($fd, 3);
                if(strlen($ipSeek) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
                fseek($fd, $ipSeek);
                $ipFlag = fread($fd, 1);
            }

            if($ipFlag == chr(2)) {
                $AddrSeek = fread($fd, 3);
                if(strlen($AddrSeek) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $ipFlag = fread($fd, 1);
                if($ipFlag == chr(2)) {
                    $AddrSeek2 = fread($fd, 3);
                    if(strlen($AddrSeek2) < 3) {
                        fclose($fd);
                        return 'System Error';
                    }
                    $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                    fseek($fd, $AddrSeek2);
                } else {
                    fseek($fd, -1, SEEK_CUR);
                }

                while(($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;

                $AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
                fseek($fd, $AddrSeek);

                while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;
            } else {
                fseek($fd, -1, SEEK_CUR);
                while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;

                $ipFlag = fread($fd, 1);
                if($ipFlag == chr(2)) {
                    $AddrSeek2 = fread($fd, 3);
                    if(strlen($AddrSeek2) < 3) {
                        fclose($fd);
                        return '- System Error';
                    }
                    $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                    fseek($fd, $AddrSeek2);
                } else {
                    fseek($fd, -1, SEEK_CUR);
                }
                while(($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;
            }
            fclose($fd);

            if(preg_match('/http/i', $ipAddr2)) {
                $ipAddr2 = '';
            }
            $ipaddr = "$ipAddr1 $ipAddr2";
            $ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
            $ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
            $ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
            if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
                $ipaddr = 'Unknown';
            }
            $return = $ipaddr;
        }
    }
    return mb_convert_encoding($return, 'utf-8', 'GB2312');
}
// 通过新浪API查询
function formSinaAPI($ip) {
    $sinaAPI = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip='. $ip;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $sinaAPI);
    $ret = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($ret);
    if(is_object($result)) {
        if ($result->ret === 1) {
            return $result->country.$result->province.$result->city.'|'.$result->isp;
        }
        else {
            return false;
        }
    }
    else {
        return false;
    }
    
}
// 通过有道API查询
function fromYoudaoAPI($ip) {
    $youdaoAPI = 'http://www.youdao.com/smartresult-xml/search.s?type=ip&q='. $ip;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $youdaoAPI);
    $ret = curl_exec($ch);
    curl_close($ch);
    $xml = simplexml_load_string($ret);
    if(is_object($xml)) {
        return $xml->product->location;
    }
    else {
        return false;
    }
    
}
/**
 * 获取 IP  地理位置
 * 淘宝IP接口
 * @Return: array
 */
function fromTaobaoAPI($ip)
{
    $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
    $ip=json_decode(file_get_contents($url));   
    if((string)$ip->code=='1'){
       return false;
    }
    $data = (array)$ip->data;
    return $data;   
}

// 通过QQwry.dat查询
function fromQQWRY($ip) {
    // http://pecl.php.net/package/qqwry 通过这个扩展查询效率最高
    if(!extension_loaded('qqwry')) {
        return convertIp($ip);
    }
    else {
        $fileName = 'qqwry.dat';
        if (file_exists($fileName)) {
            $qqwry = new qqwry($fileName);
            $arr = $qqwry->q($ip);
            $arr[0] = iconv('GB2312','UTF-8//ignore', $arr[0]);
            $arr[1] = iconv('GB2312','UTF-8//ignore', $arr[1]);
            return $arr[0].' '.$arr[1];
        }
        else {
            return false;
        }
    }
}
// 示例
function ipQuery($ip) {
    if (fromQQWRY($ip)!==false) {
        return fromQQWRY($ip);
    }
    else {
        return convertIp($ip);
    }
    /* elseif (formSinaAPI($ip)!==false) {
        return formSinaAPI($ip);
    }
    elseif (fromYoudaoAPI($ip)!==false) {
        return fromYoudaoAPI($ip);
    }
    else {
        return $ip;
    }*/
}