<?php
$dsn='mysql:dbname=データベース名;host=localhost';
$user ='ユーザー名';
$loginPass ='パスワード';
$pdo = new PDO($dsn,$user,$loginPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

$sql = "CREATE TABLE IF NOT EXISTS Data4"
."("
."id INT,"
."name char(32),"
."comment TEXT,"
."passWord TEXT,"
."submittime TEXT"
.");";
$stmt = $pdo->query($sql);

//編集モード用
$editNumber = $_POST['editnumber'];
$editPass = $_POST['editpass'];

if (!(empty($editNumber))){
    //データ取得
    $sql ='SELECT*FROM Data4';
    $stmt =$pdo->query($sql);
    $results = $stmt->fetchAll();

    $being=0;
    foreach ($results as $row){
        if ($editNumber == $row['id'] && $editPass == $row['passWord']){
            $being =2;
            $savedEditNumber = $row['id'];
            $savedEditName =$row['name'];
            $savedEditComment = $row['comment'];
            $savedEditPass = $row['passWord'];

            //編集判定用にパスワードを一旦MySQLに保存
            $sql = "CREATE TABLE IF NOT EXISTS passbox"
            ."("
            ."passWord TEXT"
            .");";
            $stmt = $pdo->query($sql);
            $sql = $pdo ->prepare("INSERT INTO passbox(passWord) VALUES(:passWord)");
            $sql -> bindParam(':passWord',$savedEditPass,PDO::PARAM_STR);
            $sql -> execute();


        }elseif ($editNumber == $row['id'] && $editPass != $row['passWord'] && $being !=2){
            //編集番号が一致しpassが異なるときに$being=1とする。ただし、すでに番号もpassも一致する投稿が存在する場合は適用外
            //この処理が行われた後に番号もpassも一致する投稿が現れても上のif文が適用されるため問題なし。
            $being =1;
        }
    }
    if ($being ==1){
        echo "パスワードが違います。"."<br>";
    }elseif ($being ==0){
        echo "その投稿は存在しません。"."<br>";
    }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>MySQLとの連携</title>
</head>
<body>

<!--通常投稿フォーム-->
<h2>通常投稿フォーム</h2>
<form method="POST" action="./simpleForum.php">
    <input type="hidden" name="invisible" value="<?php echo $savedEditNumber; ?>">
    <input type="text" name="name" placeholder="名前" value="<?php echo $savedEditName; ?>"><br>
    <input type="text" name="comment" placeholder="コメント" value="<?php echo $savedEditComment; ?>"><br>
    <input type="text" name="pass" placeholder="パスワード" value="<?php echo $savedEditPass; ?>">
    <input type="submit" value="送信"><br><br>

</form>


<!--削除フォーム-->
<h2>削除フォーム</h2>
<form method="POST" action="./simpleForum.php">
    <input type="number" name="deletenumber" placeholder="削除対象番号"><br>
    <input type="text" name="deletepass" placeholder="パスワード">
    <input type="submit" value="削除"><br><br>

</form>

<!--編集フォーム-->
<h2>編集フォーム</h2>
<form method="POST" action="./simpleForum.php">
    <input type="number" name="editnumber" placeholder="編集対象番号"><br>
    <input type="text" name="editpass" placeholder="パスワード">
    <input type="submit" value="編集"><br><br>    

</form>

<?php

//通常投稿フォーム入力受取
$comment = $_POST['comment'];
$userName = $_POST['name'];
$passWord = $_POST['pass'];

//削除フォーム入力受取
$deleteNumber =$_POST['deletenumber'];
$deletePass =$_POST['deletepass'];

//非表示編集番号受け取り
$invisibleNumber = $_POST['invisible'];

//UNIX TIMESTAMP取得、格納
$timestamp = time();
$date = date("Y年m月d日 H時i分", $timestamp);

//通常投稿モード 
if (empty($deleteNumber) && empty($editNumber) && empty($invisibleNumber)){
    if (!(empty($comment)) && !(empty($userName)) && !(empty($passWord))){
        
        //投稿番号取得
        $sql = 'SELECT*FROM Data4';
        $stmt = $pdo->query($sql);
        $contents = $stmt ->fetchAll();
        for ($i=0; $i<=count($contents); $i++){
            $id =$i+1;
        }
        
        //MySQLにデータ入力
        $sql = $pdo ->prepare("INSERT INTO Data4(id,name,comment,passWord,submittime) VALUES(:id,:name,:comment,:passWord,:submittime)");
        $sql -> bindParam(':id',$id,PDO::PARAM_INT);
        $sql -> bindParam(':name',$userName,PDO::PARAM_STR);
        $sql -> bindParam(':comment',$comment,PDO::PARAM_STR);
        $sql -> bindParam(':passWord',$passWord,PDO::PARAM_STR);
        $sql -> bindParam(':submittime',$date,PDO::PARAM_STR);
        $sql -> execute();
            
        //投稿内容結合
        $submit = "ID:".$id."　投稿者名:".$userName."　投稿内容:".$comment."　パスワード:".$passWord."　投稿日時：".$date.'<br>';
        //投稿内容出力
        echo "ご入力ありがとうございます。"."<br>";
        echo $submit;
            
        //ログ取得
        $sql ='SELECT*FROM Data4';
        $stmt = $pdo->query($sql);
        $results = $stmt ->fetchAll();
        //ログ表示
        echo "<br>"."<ログ>"."<br>";
        foreach ($results as $row){
            echo "ID:".$row['id']."　投稿者名:".$row['name']."　投稿内容:".$row['comment']."　パスワード:".$row['passWord']."　投稿日時：".$row['submittime'].'<br>';
        }

    }elseif(empty($comment)){
        echo "コメントが入力されていません。";
    }elseif(empty($userName)){
        echo "名前が入力されていません。";
    }elseif(empty($passWord)){
        echo "パスワードが入力されていません。";
    }
        
}

//削除モード
if (!(empty($deleteNumber)) && empty($editNumber)){
    //削除前データ取得
    $sql ='SELECT*FROM Data4';
    $stmt =$pdo->query($sql);
    $results = $stmt->fetchAll();
    foreach($results as $row){
        if ($row['id'] == $deleteNumber && $row['passWord'] == $deletePass){
            $exist =2;
            //投稿削除
            $sql = 'delete from Data4 where id=:id AND passWord=:passWord';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id',$deleteNumber,PDO::PARAM_INT);
            $stmt->bindParam(':passWord',$deletePass,PDO::PARAM_STR);
            $stmt->execute();

            //ログ取得
            $sql ='SELECT*FROM Data4';
            $stmt = $pdo->query($sql);
            $results = $stmt ->fetchAll();
            //ログ表示
            echo "<br>"."<ログ> 投稿No.".$row['id']."が削除されました。"."<br>";
            foreach ($results as $row){
                echo "ID:".$row['id']."　投稿者名:".$row['name']."　投稿内容:".$row['comment']."　パスワード:".$row['passWord']."　投稿日時：".$row['submittime'].'<br>'; 
            }
        }elseif($row['id'] == $deleteNumber && $row['passWord'] != $deletePass && $exist !=2){
            //削除番号が一致しpassが異なるときに$exist=1とする。ただし、すでに番号もpassも一致する投稿が存在する場合は適用外
            //この処理が行われた後に番号もpassも一致する投稿が現れても上のif文が適用されるため問題なし。
            $exist =1;
            
        }
    }
    if ($exist == 0){
        echo "その投稿は存在しません。";
    }elseif($exist ==1){
        echo "パスワードが違います。";
    }
   
}

//編集モード
if (!(empty($invisibleNumber))){
    //編集判定用pass取得
    $sql ='SELECT*FROM passbox';
    $stmt =$pdo->query($sql);
    $forConfirm = $stmt->fetchAll();
    foreach($forConfirm as $row){
        $passForConfirm=$row['passWord'];
    }
    //編集判定用pass削除
    $sql='delete from passbox where passWord=:confirm';
    $stmt =$pdo->prepare($sql);
    $stmt ->bindParam(':confirm',$passForConfirm,PDO::PARAM_STR);
    $stmt->execute();

    //編集前データ取得
    $sql ='SELECT*FROM Data4';
    $stmt =$pdo->query($sql);
    $results = $stmt->fetchAll();
    foreach ($results as $row){
        if ($row['id'] == $invisibleNumber && $row['passWord']== $passForConfirm){
            //投稿編集
            $sql = 'update Data4 set name=:name,comment=:comment,passWord=:editPassWord,submittime=:submittime where id=:id AND passWord=:confirm';
            $stmt =$pdo->prepare($sql);
            $stmt->bindParam(':id',$invisibleNumber,PDO::PARAM_INT);
            $stmt->bindParam(':name',$userName,PDO::PARAM_STR);
            $stmt->bindParam(':comment',$comment,PDO::PARAM_STR);
            $stmt->bindParam(':editPassWord',$passWord,PDO::PARAM_STR);
            $stmt->bindParam(':submittime',$date,PDO::PARAM_STR);
            $stmt->bindParam(':confirm',$passForConfirm,PDO::PARAM_STR);
            $stmt->execute();

            //ログ取得
            $sql ='SELECT*FROM Data4';
            $stmt = $pdo->query($sql);
            $results = $stmt ->fetchAll();
            //ログ表示
            echo "<br>"."<ログ> 投稿No.".$row['id']."を編集しました。"."<br>";

        }


    }
    foreach ($results as $row){
        echo "ID:".$row['id']."　投稿者名:".$row['name']."　投稿内容:".$row['comment']."　パスワード:".$row['passWord']."　投稿日時：".$row['submittime'].'<br>'; 
    }
}
?>

</body>
</html>