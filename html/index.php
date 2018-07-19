<?php
$access_key         = "AKIAJP3IJN2XECN4YV5A"; //Access Key
$secret_key         = "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"; //Secret Key
$my_bucket          = "banking-brd-bucket"; //bucket name
$region             = "ap-south-1"; //bucket region
$success_redirect   = 'https://s3.ap-south-1.amazonaws.com/static-webpageforfileupload/index.html'; //URL to which the client is redirected upon success (currently self) 
$allowd_file_size   = "1048579"; //1 MB allowed Size
date_default_timezone_set('America/New_York');

//dates
$short_date         = gmdate('Ymd'); //short date
$iso_date           = gmdate("Ymd\THis\Z"); //iso format date
$expiration_date    = gmdate('Y-m-d\TG:i:s\Z', strtotime('+1 hours')); //policy expiration 1 hour from now

//POST Policy required in order to control what is allowed in the request
//For more info http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-HTTPPOSTConstructPolicy.html
$policy = utf8_encode(json_encode(array(
                    'expiration' => $expiration_date,  
                    'conditions' => array(
                        array('acl' => 'public-read'),  
                        array('bucket' => $my_bucket), 
                        array('success_action_redirect' => $success_redirect),
                        array('starts-with', '$key', ''),
                        array('content-length-range', '1', $allowd_file_size), 
                        array('x-amz-credential' => $access_key.'/'.$short_date.'/'.$region.'/s3/aws4_request'),
                        array('x-amz-algorithm' => 'AWS4-HMAC-SHA256'),
                        array('X-amz-date' => $iso_date)
                        )))); 

//Signature calculation (AWS Signature Version 4)   
//For more info http://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-authenticating-requests.html  
$kDate = hash_hmac('sha256', $short_date, 'AWS4' . $secret_key, true);
$kRegion = hash_hmac('sha256', $region, $kDate, true);
$kService = hash_hmac('sha256', "s3", $kRegion, true);
$kSigning = hash_hmac('sha256', "aws4_request", $kService, true);
$signature = hash_hmac('sha256', base64_encode($policy), $kSigning);
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Aws S3 Direct File Uploader</title>
<style>
        body {
            background-color: #66a5f0;
        }
        
        h1 {
            color: #C26356;
            font-size: 30px;
            font-family: Menlo, Monaco, fixed-width;
        }
        
        p {
            color: white;
            font-family: "Source Code Pro", Menlo, Monaco, fixed-width;
        }
    </style>
</head>
<body>
<h1>Welcome to file upload page for BRD submission</h1>
<p>This page will be used to upload your BRD to our system</p>
<p>If upload is successful you will be redirected to landing page with link to see the output</p>
<p>******************************************************************************************</p>
<p>******************************************************************************************</p>
<img src="ext.jpg" alt="cognizant logo" width="100" height="60">
<form action="http://<?= $my_bucket ?>.s3.amazonaws.com/" method="post" enctype="multipart/form-data">
<input type="hidden" name="key" value="${filename}" />
<input type="hidden" name="acl" value="public-read" />
<input type="hidden" name="X-Amz-Credential" value="<?= $access_key; ?>/<?= $short_date; ?>/<?= $region; ?>/s3/aws4_request" />
<input type="hidden" name="X-Amz-Algorithm" value="AWS4-HMAC-SHA256" />
<input type="hidden" name="X-Amz-Date" value="<?=$iso_date ; ?>" />
<input type="hidden" name="Policy" value="<?=base64_encode($policy); ?>" />
<input type="hidden" name="X-Amz-Signature" value="<?=$signature ?>" />
<input type="hidden" name="success_action_redirect" value="<?= $success_redirect ?>" /> 
<input type="file" name="file" />
<input type="submit" value="Upload File to S3" />
</form>
<?php
//After success redirection from AWS S3
if(isset($_GET["key"]))
{
    $filename = $_GET["key"];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(in_array($ext, array("jpg", "png", "gif", "jpeg"))){
        echo '<hr />Image File Uploaded : <br /><img src="//'.$my_bucket.'.s3.amazonaws.com/'.$_GET["key"].'" style="width:100%;" />';
    }else{
        echo '<hr />File Uploaded : <br /><a href="http://'.$my_bucket.'.s3.amazonaws.com/'.$_GET["key"].'">'.$filename.'</a>';
    }
}
?>
</body>
</html>
