# F1 DB - User Manual

## Init:
$db = new DB()  
$db->connect($app->env->db1)  

## Query:
$db->query('tblusers')  
$db->query('tblusers u LEFT JOIN tbluserroles r ON r.id = u.role_id')  
->select('u.id, u.desc AS bio, r.desc AS role')  
->select('count(u.id) as TotalUsers')  
->select('\*, CONCAT(firstname," ",lastname) as name')  

->where('refno IS NULL')  
->where('pakket_id=?', $pakket_id)  
->where('id>?', $id, ['ignore'=>null])  
->where('tag_id', $arrTagIDs, ['test'=>'IN'])  
->where('tag_id', ['one','two','three'], ['test'=>'NOT IN'])  
->where('tag_id NOT IN (?,?,?)' , ['one','two','three'], ['ignore'=>null])  
->where('tag_id IN (' . implode(',', $arTagIDs) . ')') // Unsafe  
->where(  

> $db->subQuery()  
>> ->where('date1 BETWEEN (?,?)', [$minDate,$maxDate])         // Exclusive  
>> ->where('date2', [$fromDate,$toDate], ['test'=>'FROM TO'])  // Inclusive  
>> ->where('age'  , [$minAge  ,$maxAge], ['test'=>'FROM TO'])  
>> ->orWhere('is_weekend IS NULL')  

)  

->where('tagCount<?', $db->subQuery('tblconfig')->getFirst()->max_tags)  

->orWhere('CONCAT(firstname," ",lastname) LIKE ?)', "%$nameTerm%")  
->orWhere('name LIKE ?', "%$nameTerm%", ['ignore'=>[null,'']])  
->orWhere("name LIKE '$nameTerm%'") // Unsafe  

->orderBy('date')  // Defaults to 'asc'  
->orderBy('date desc, time')  

->groupBy('age')  

->having('TotalUsers>=?', 10)  
->having('TotalUsers<=?', 20)  
->orHaving(  

> $db->subQuery()  

>> ->where('Awards<?', 3)  
>> ->where(  
>>> $db->subQuery()  
>>>> ->where('Skill>? AND Skill<?', [3000,5000])  
>>>> ->orWhere('TotalPoints>?', 1000)  

>> )  

)  

->limit(100)  
->limit(100, 15)  
->limit($itemspp, $offset)  

->indexBy('id')  
->indexBy('type,color')        // Resulting index format = "{type}-{color}"  
->indexBy('type,color', '\_')  // Resulting index format = "{type}\_{color}"  
->indexBy('type,color', '')    // Resulting index format = "{type}{color}"  

->getAll()  
->getAll('id,desc')  
->getAll('DISTINCT name, desc AS bio')  

->getFirst()  
->getFirst('id,desc')  

->count()  


## Insert:
$db->insertInto('tbl_users', $objUser1)  
$db->insertInto('tbl_users', [$objUser1, $objUser2, ...])  
$db->insertInto('tbl_users', $arrUser1)  
$db->insertInto('tbl_users', [$arrUser1, $arrUser2, ...])  


## Update:
$db->query('tblusers')  
->where('id=?', 1)  
->update(['name' => 'John']);  


$db->batchUpdate('tblusers',  
[  

> ['id'=>1, 'name'=>'john', 'age'=>27],  
> ['id'=>2, 'name'=>'jill', 'age'=>29]  

],[

> 'where' => 'id=?',  
> 'only'  => 'name,age'  

]);  


$db->updateOrInsert('tblusers',
[  

> ['id'=>1, 'name'=>'john', 'age'=>27],  
> ['id'=>2, 'name'=>'jill', 'age'=>29]  

],[  

> 'excl'  => 'age'  

]);  


## Delete:
$db->query('tblusers')  
->where('id=?', 1)  
->delete();  

 $db->query('tblusers')  
 ->where('id', $arrayOfIds, ['test'=>'IN'])  
 ->delete();  

 $db->query('tblusers')  
 ->where('age<?', 18)  
 ->delete();  


## Create:
try {  

> $dp->createTable('banned_ips', '(  

>> `id` int(11) NOT NULL AUTO_INCREMENT,  
>> `ip` varchar(30) NOT NULL,  
>> `attempt_count` int(11) DEFAULT 1,  
>> `created_on` datetime DEFAULT current_timestamp(),  
>> PRIMARY KEY (`ip`)  

> ) ENGINE=MyISAM DEFAULT CHARSET=latin1;' );  

}  
catch (PDOException $e) {  

> $app->log->error($e->getMessage());  

}  

PS: Can also set '$checkIfExists' param = TRUE to prevent  
generating exceptions when the table already exists!  

$dp->createTable =~> CREATE TABLE [IF NOT EXISTS] ...  