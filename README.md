# Citrine PDO Class

This is an experiment with magic overloading in PHP. This is a database class that offers simple PDO functioning.

## Requires
* **PHP 5.3**
* **PHP PDO data source compatibility**

Getting Started
-------------------------

To create a new instance of this class, you will need to provide

* Server Location - "localhost"
* Database Username - "myUserName"
* Database Password - "12345"
* Database Name - "mydb"
* Driver to use - optional, default is 'mysql'
* Override DSN - Or, write your own

```php
  <?php
	require_once 'CitrineExpPDO.php';
	//Assuming MySQL
    $db = new CitrineExpPDO('localhost', 'myUserName', '12345', 'mydatabase');
```

Using Magic Overloads
-------------------------

This helper class uses PHP magic overload methods. Rather than sending a table name as a string, it is passed as a method to the class. It is also possible to just use the class without the magic, see below to **Existing Methods**.

#### SELECT

```php
	// SELECT * FROM members_table
	$result = $db->members_table();
```

#### SELECT...WHERE

```php
	// SELECT password_hash FROM members_table WHERE email = 'user@email.com'
	$where = array();
	$where['email'] = $email_address;
	$db->members_table('password_hash', $where);
```

#### Comparison WHERE

```php
	// SELECT display_name FROM members_table WHERE member_id > 5
	$where = array();
	$where['member_id'] = '> 5';
	$result = $db->members_table('display_name', $where);
```

#### OR/AND/NOT

```php
	// SELECT display_name FROM members_table WHERE member_id = 1 OR member_id = 2
	$where = array();
	$where['member_id'] = '5';
	$where['OR member_id'] = '2';
	$result = $db->members_table('display_name', $where);
```

#### WHERE IN

```php
	// SELECT display_name FROM members_table WHERE member_id IN ('1','2','3')
	$where = array();
	$where['member_id'] = array(1,2,3);
	$result = $db->members_table('display_name', $where);
```

#### WHERE LIKE

```php
	// SELECT display_name FROM members_table WHERE display_name LIKE "%J%"
	$where = array();
	$where['display_name'] = '%J%';
	$result = $db->members_table('display_name', $where);
```

#### ORDER BY, LIMIT

```php
	// SELECT display_name FROM members_table ORDER BY member_id ASC LIMIT 2,3
	$result = $db->members_table('password_hash', false, 'member_id ASC', '2,3');
```

#### INSERT INTO

```php
	// INSERT INTO members_table (display_name, password_hash) VALUES ('John', '2ef94%.g31')
	$insert = array();
	$insert['display_name'] = 'John';
	$isnert['password_hash'] = '2ef94%.g31'
	$result = $db->members_table($insert);
```

#### UPDATE

```php
	// UPDATE members_table SET display_name = 'Jane' WHERE member_id = 3
	$update = $where = array();
	$update['display_name'] = 'Jane';
	$where['member_id'] = 3;
	$result = $db->members_table($update, $where);
```

#### DELETE

```php
	// DELETE FROM members_table WHERE member_id = 3
	$where = array();
	$where['member_id'] = 3;
	$result = $db->members_table(DELETE, $where);
```

Existing Methods
-------------------------

Experiment aside, typical select/single/update methods already exist as well.

Most importantly, there is custom query ability which will execute and another which simply returns a prepared statement object.

```php
	$results = $db->custom($sql);
```

and

```php
	$pdostatement = $db->prep($sql);
```
