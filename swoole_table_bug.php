<?php 

//测试共享内存
$table= new Swoole\Table(131072);
$table->column('col0', Swoole\Table::TYPE_INT, 4);
$table->column('col1', Swoole\Table::TYPE_INT, 4);
$table->column('col100', Swoole\Table::TYPE_INT, 8);
$table->column('col2', Swoole\Table::TYPE_STRING, 32);
$table->column('col3', Swoole\Table::TYPE_STRING, 32);
$table->column('col4', Swoole\Table::TYPE_STRING, 4);
$table->column('col5', Swoole\Table::TYPE_STRING, 24);
$table->column('col6', Swoole\Table::TYPE_STRING, 255);
$table->column('col7', Swoole\Table::TYPE_INT, 1);
$table->column('col8', Swoole\Table::TYPE_INT, 4);
$table->column('col9', Swoole\Table::TYPE_INT, 4);
$table->column('col10', Swoole\Table::TYPE_INT, 4);
$table->column('col11', Swoole\Table::TYPE_INT, 4);
$table->column('col12', Swoole\Table::TYPE_INT, 4);
$table->column('col13', Swoole\Table::TYPE_INT, 4);
$table->create();

echo "测试开始\n";
echo "模拟插入65535个记录\n";
//模拟插入65535个记录
for($i = 1; $i < 65536; $i++){
	$data = array(
		'col0' => $i,
		'col1' => $i - 1,
		'col100' => mt_rand(10000000000, 19999999999),
		'col2' => md5("asfsadfasdfsda"),
		'col3' => md5("asfsadfasdfsda"),
		'col4' => 'ew45',
		'col5' => 'wahaha',
		'col6' => 'asdjasdasdasdasdsfdfsdfsdfdsfjlskdfjldkjfasjdhaskjdhakdjfhksjdfhkjsdhfksjdfhjksdfhskfhskjdfhksdjhfkjsdhffsdddsjdhfkjsdhlksdhjlsdkjfh',
		'col7' => 1,
		'col8' => 11231231232,
		'col9' => 15123123123,
		'col10' => 2352342342,
		'col11' => 14232,
		'col12' => 13123123,
		'col13' => 30,
	);
	$table->set("u".$i, $data);
}
echo "插入完成\n";
		
$server = new Swoole\Http\Server("0.0.0.0", 9501);
$server->table = $table;
$server->set([
	'worker_num' => 4
]);


$server->on('workerStart', function ($server, $worker_id){
	//【请手动调整这个值，从小到大】
	// 从共享内存中删除的key的个数，保证每个worker都删除同样的key，  
	// 当这个值日从小增大到5000+ 以后，删除key的结果变得越来越不稳定，大于65000后，结果很诡异
	$num_to_del = 65000;
	
	for($i = 1; $i <= $num_to_del; $i++){
		$server->table->del("u".$i);
	}
	
	sleep(2);//等待，确保每个worker的删除工作都完成
	
	echo "worker ".$worker_id." 从共享内存删除".$num_to_del."个key后， 期望剩余".(65535-$num_to_del)." 实际共享内存还剩".$server->table->count()."个key\n";
});

$server->on('request', function ($request, $response){});

$server->start();
