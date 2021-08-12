<?php
###https://blog.csdn.net/whzhaochao/article/details/49126037 造数据
set_time_limit(0);// 设置不超时
ini_set('memory_limit', '1024M');// 设置最大内存 导出一百万如果不设置为1024 则导出不了
set_exception_handler(function ($error) {
    echo 1;
    exit;
});
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $data = [
        "code" => $errno,
        "msg"  => $errstr,
        "file" => $errfile,
        "line" => $errline,
    ];
    print_r($data);
    echo "<br/>";
    exit;
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        $data    = [
            "code" => $errno,
            "msg"  => $errstr,
            "file" => $errfile,
            "line" => $errline,
        ];
        print_r($data);
        exit;
    }
});
$model = new Qushu();
echo date("H:i:s",time())."<br/>";
$model->getDg();
echo date("H:i:s",time())."<br/>";
class Qushu {
    public function getDg() {
        $header = [
            'id',
            'user_id',
            'vote_id',
            'group_id',
            'create_time'
        ];
        $output = $this->csvSet("导出表名", $header);
        $data   = $this->getCsv();
        $i = 0;
        foreach ($data as $k => $v) {
            //输出csv内容
            fputcsv($output, array_values($v));
            $i++;
        }
        //关闭文件句柄
        fclose($output) or die("can‘t close php://output");
    }

    /**
     *获取csv内容 使用 yield
     */
    public function getCsv() {
       try{
           $mysql = new PDO('mysql:host=127.0.0.1;dbname=test', "root", "123456", [
//               PDO::ATTR_PERSISTENT => true
           ]);;
       }catch (Throwable $exception){
           die("数据库连接失败" . $exception->getMessage());
       }
        $mysql->query("SET NAMES 'UTF8'");
        $countdata = $mysql->query("select count(1) from vote_record_memory;");
        $rows = $countdata->fetch();
        $rowCount = $rows[0];
        $limit =5000;
        $page = ceil($rowCount / $limit);
        for ($i = 1; $i <= $page; $i++) {
            $offset = ($i - 1) * $limit;//偏移量
            $rowsearch = $mysql->query("select * from vote_record_memory LIMIT $offset,$limit");
            $data=$rowsearch->fetchAll(PDO::FETCH_ASSOC);
            if(empty($data)){
                continue;
            }
//            print_r($data);die;
            // 这里还可以继续优化，我们前面设置了最大的内存是1024M就是因为这里一下子从数据库读取了100W数据
            // 我们还是可以分页获取 比如十万取一次
            foreach ($data as $key => $val) {
                $a = [
                    $val['id'],
                    $val['user_id'],
                    $val['vote_id'],
                    $val['group_id'],
                    $val['create_time']
                ];
                yield $a;// 如果不了解生成器的同学、先去了解yield
            }
        }
    }

    /**设置csv*/
    public function csvSet($name, $head) {
        try {
            //设置内存占用
            //为fputcsv()函数打开文件句柄
            $output = fopen('php://output', 'w') or die("can‘t open php://output");
            //告诉浏览器这个是一个csv文件
            header("Content-Type: application/csv");
            header("Content-Disposition: attachment; filename=$name.csv");
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');
            // 文件名转码
            $name = iconv('utf-8', 'gbk', $name);
            //输出表头
            foreach ($head as $i => $v) {
                //CSV的Excel支持GBK编码，一定要转换，否则乱码
                $head[$i] = iconv('utf-8', 'gbk', $v);
            }
            fputcsv($output, $head);
            return $output;
        } catch (Exception $e) {
        }
    }
}

