---
title: Common SQL errors for Laravel Developers
slug: sqlerrm
created_at: 2021-11-13
updated_at: 2021-11-13
published_at: 2021-11-13
author: Tray2
summary: Why write this little guide since Laravel uses Eloquent to talk to the database? The answer is simple, the errors the database returns are SQL errors and if you don't know how to interpret them then your life as a developer will be harder.
image: sqlerrm.png
---

## Common SQL errors and how to solve them in Laravel

Why write this little guide since Laravel uses Eloquent to talk to the database?
The answer is simple, the errors the database returns are SQL errors and if you don't know how to 
interpret them then your life as a developer will be harder.

## Table Of Contents

1. [Naming convention is your friend.](#naming-conventions-is-your-friend)
1. [Always validate your data.](#always-validate-your-data)
1. [The missing table or view AKA SQLSTATE[42S02]:](#the-missing-table-or-view-aka-sqlstate-42s02)
1. [The foreign key issue AKA SQLSTATE[HY000]: General error: 1215](#the-foreign-key-issue-aka-sqlstate-hy000)
1. [The troublesome child AKA ERROR 1451 (23000): Cannot delete or update a parent row:](#the-troubleseome-child-aka-error-1451)
1. [The refusal to add or update AKA ERROR 1452 (23000): Cannot add or update a child row:](#the-refusal-to-add-or-update-aka-error-1452)
1. [The irksome null value AKA ERROR 1048 (23000): SQLSTATE[23000]: Integrity constraint violation:](#the-irksome-null-value-aka-error-1048)
1. [The missing default value AKA SQLSTATE[HY000]: General error: 1364 Field <field> doesn't have a default value](#the-missing-default-value-aka-sqlstate-hy000)
1. [The truncated value AKA SQLSTATE[22001]: String data, right truncated:](#the-truncated-value-aka-sqlstate-22001)
1. [The value issue AKA SQLSTATE[HY000]: General error: 1366 Incorrect <type> value:](#the-value-issue-aka-sqlstate-hy000)

 
 ### Naming convention is your friend <a id="naming-conventions-is-your-friend"></a>

 Some might not agree with the naming conventions that Laravel uses but by following 
 them your life becomes so much easier. You get a lot of stuff out of the box so to say if 
 you stick to the defaults. They are not that many, so they are pretty easy to remember.

 1. All tables should be in plural form.
 1. Pivot tables should be singular form of the tables joined with an underscore in alphabetical order.
 1. Primary keys should be named id
 1. Foreign keys should be named in the singular form of the table the reference and the word id seperated by an underscore.
 1. Table names consisting of more than one word should have the words seperated by an underscore.

 #### 1. All tables are in plural form

 If we look at a simple blog application, we need somewhere to store the username, 
 the blog post and any comments that visitors might make to the post. So how should the tables be named. 
 Well it's quite simple we have the following parts in our application.

 * A user
 * A blogpost
 * A comment

 The tables for this should then be.

 * users
 * posts
 * comments

 How about when the plural form or the word is bent a little differently than just add in an `s` to the word.
 Then we should use the proper grammatical for it.

 * delivery would be deliveries

 Laravel uses a pluralization library for converting model names to plural form behind the scenes.

 #### 2. Pivot tables should be singular form of the tables joined with an underscore in alphabetical order.


 A pivot table is just a regular table joining two other tables together via their primary keys. 
 If we look at a book registry application we might have the following tables

 * books
 * libraries

 Now a library has many books and a book can be in many libraries. 
 So to solve this many-to-many relation we use a pivot table. 
 The naming convention is that you take the table names in their singular form 
 and snake case them together in alphabetical order. 
 In our case with books and libraries it would become `book_library`.

 ### 3. Primary keys are named id

 A primary key is the unique identifier in a table. It in most cases it's numeric or a UUID. 
 You can use another kind of unique value, but it can cause your database to grow more than 
 necessary when that identifier is used in a relationship with another table. 
 The name for this unique identifier should be `id`.

 ### 4. Foreign keys should be named in the singular form of the table reference and the word id seperated by an underscore.

 A foreign key is used to define the relation between two tables. If we take our blog example you add a
 foreign key to the posts' table to tell the database which user has created the post or as in the book 
 registry example you use both the id from the books table and the libraries' table in your pivot table.

 You name the column with the singular form of the table and then an underscore followed by the column name 
 of the primary key so in the posts' table the foreign key linking the post to the user is `user_id`.

 ### 5. Table names consisting of more than one word should have the words seperated by an underscore.
 
 If you have tables that you need more than one word to describe should have both words in the 
 name seperated by an underscore. So if you have a table that contains party planners you 
 name the table `party_planners`. The model name will also contain both words but without the underscore, `PartyPlanner`.

#### Stick to these conventions and most of the problem described later will never occur.

## Always validate your data <a id="always-validate-your-data"></a>

Validation is not only a way to make sure the user doesn't send you any insecure data for you to store in your database.
The second and maybe most important function of validation is to make sure you don't display any nasty SQL error messages
in your application. The errors that can't be avoided by sticking to the naming convention will for the most part be 
avoided with proper validation. There are two forms of validation, and I suggest you use both of them.

* Client side validation
* Server side validation

The client side validation could be as simple as adding `required` to the form elements that are not optional. 
This prevents most users from sending the form.
You can of course add more complex validations if you like on the client side.

Server side validation should always be present to avoid any unpleasant error messages or inserts of malicious data. 
You can use either validation in your controller or via a FormRequest.

## The missing table or view AKA SQLSTATE[42S02]: <a id="the-missing-table-or-view-aka-sqlstate-42s02"></a>

Sometimes the SQL error messages can be a bit cryptic but this one is quite straight forward

```sql
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'laraveldb.books' doesn't exist (SQL: select * from `books`)
```
This means that either the table or database view does not exist in the database. 
This line is the kicker here and the one that will tell you what is wrong, `Table 'laraveldb.books' doesn't exist`. 
So either there is no table or view called `books` in our database, the table name is misspelled in your migration,
or you have deviated from the standard naming convention. The easiest way to check this is open a connection to your
database with an application like SequelPro, TablePlus or HeidiSQL. 
In this guide I will use the commandline and run the following command.

```sql
SHOW TABLES;
```

This command gives a list of all tables and database views.

### The missing table.

```sql
mysql> SHOW TABLES;
+---------------------+      
| Tables_in_laraveldb |
+---------------------+
| failed_jobs         |
| migrations          |
| password_resets     |
| users               |
+---------------------+
4 rows in set (0.00 sec)
```

As you can see there is no table called `books` so the solution is to create the table with a migration then 
migrate the database, if you already have a migration for it, it will be created when the migration is run.

```bash
php artisan migrate
```

Then run the show tables query again and see that the books' table is created.

```sql
mysql> SHOW TABLES;
+---------------------+
| Tables_in_laraveldb |
+---------------------+
| books               |
| failed_jobs         |
| migrations          |
| password_resets     |
| users               |
+---------------------+
5 rows in set (0.00 sec)
```

### The misspelled table name or the Non-conventional table name.

```sql
mysql> SHOW TABLES;
+---------------------+
| Tables_in_laraveldb |
+---------------------+
| book                |
| failed_jobs         |
| migrations          |
| password_resets     |
| users               |
+---------------------+
4 rows in set (0.00 sec)
```

If you are in development you can update the existing migration and name the table correctly and run 

#### Caution this will delete all you data.

```bash
php artisan migrate:fresh
```

Or you can create a new migration and rename the table.

There are ways to tell Laravel that you use another table name than the one the naming convention dictates you should use,
but I recommend sticking with the defaults.


## The foreign key issue AKA SQLSTATE[HY000]: General error: 1215 <a id="the-foreign-key-issue-aka-sqlstate-hy000"></a>

This error occurs when you run your migrations, and you have a foreign key that you put a constraint on.

```
SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint (SQL: alter table `books` add constraint 
`books_author_id_foreign` foreign key (`author_id`) references `authors` (`id`))
```

The error message isn't the best since it can be caused by two different error.

1. The type differs between the primary key of the table the constraint is referencing and the foreign key.
1. The order the tables are migrated in.

We start with the wrong column type.

Our authors table

```php
Schema::create('authors', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

And our books table
```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->unsignedInteger('author_id');
    $table->timestamps();
    $table->foreign('author_id')->references('id')->on('authors');
});
```

As you can see in the first migration we create an id in the authors table, this id is of the type unsigned
big integer since the release of Laravel 7. 

We create the foreign key in the second migration, and we tell it to use the type unsigned integer but 
the id column is of the type unsigned big integer as we can see if we run 

```sql
DESC users;
```

in our database

```sql
+-------------------+---------------------+------+-----+---------+----------------+
| Field             | Type                | Null | Key | Default | Extra          |
+-------------------+---------------------+------+-----+---------+----------------+
| id                | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| name              | varchar(255)        | NO   |     | NULL    |                |
| email             | varchar(255)        | NO   | UNI | NULL    |                |
| email_verified_at | timestamp           | YES  |     | NULL    |                |
| password          | varchar(255)        | NO   |     | NULL    |                |
| remember_token    | varchar(100)        | YES  |     | NULL    |                |
| created_at        | timestamp           | YES  |     | NULL    |                |
| updated_at        | timestamp           | YES  |     | NULL    |                |
+-------------------+---------------------+------+-----+---------+----------------+
```

So the solution to this error is simply to change the type of our foreign key in the books' table migration.

```php
 Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->unsignedBigInteger('author_id');
    $table->timestamps();
    $table->foreign('author_id')->references('id')->on('authors');
});
```

The other issue is in which order the tables are migrated. They are migrated in the order of the filenames. 
Our two migrations are named.

* 2020_12_24_174025_create_books_table
* 2020_12_24_174111_create_authors_table

Our books' table is created first, and then we add the authors' table so when we are trying to add the 
foreign key constraint the authors' table does not exist. There are two ways of fixing this.

1. Rename the migration file for the authors table, so it's ran before the books' migration.
1. Put all constraint in a secondary file and run it last.

I, much prefer the first way to do it. However, it can depend on where you are in your development progress.

So we rename the authors_table migration.

* 2020_12_24_174000_create_authors_table
* 2020_12_24_174025_create_books_table

And run migrate refresh command again.

And then we get a successful migration.

```bash
Migrating: 2020_12_24_174000_create_authors_table
Migrated:  2020_12_24_174000_create_authors_table (17.89ms)
Migrating: 2020_12_24_174025_create_books_table
Migrated:  2020_12_24_174025_create_books_table (57.51ms)
```

### Note:
 You don't have to use a foreign key constraint, but it's recommended since you 
 can get some cascading effects when you manipulate the data in a table. 
 If you delete a record in one table the database can if you want to delete all child records 
 connected to the id in the table you are deleting from. this brings us to our next error.

## The troublesome child AKA ERROR 1451 (23000): Cannot delete or update a parent row: <a id="the-troubleseome-child-aka-error-1451"></a>

Now this error has to do with a foreign key constraint in our database.

If we run

```sql
DESC books;
```
 We see this from our previous example

 ```sql
mysql> desc books;
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| title      | varchar(255)        | NO   |     | NULL    |                |
| author_id  | bigint(20) unsigned | NO   | MUL | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
5 rows in set (0.00 sec)
```
If we look att the author_id we see that we have a value in the key column, MUL. 
That means in our case that it references another column in another table. 
We can list those constraints with the following command.

```sql
SELECT *
FROM information_schema.referential_constraints
WHERE constraint_schema = 'LARAVELDB';
```
The question in this case would give the following result.

```sql
+--------------------+-------------------+-------------------------+---------------------------+--------------------------+------------------------+--------------+-------------+-------------+------------+-----------------------+
| CONSTRAINT_CATALOG | CONSTRAINT_SCHEMA | CONSTRAINT_NAME         | UNIQUE_CONSTRAINT_CATALOG | UNIQUE_CONSTRAINT_SCHEMA | UNIQUE_CONSTRAINT_NAME | MATCH_OPTION | UPDATE_RULE | DELETE_RULE | TABLE_NAME | REFERENCED_TABLE_NAME |
+--------------------+-------------------+-------------------------+---------------------------+--------------------------+------------------------+--------------+-------------+-------------+------------+-----------------------+
| def                | laraveldb         | books_author_id_foreign | def                       | laraveldb                | PRIMARY                | NONE         | RESTRICT    | RESTRICT    | books      | authors               |
+--------------------+-------------------+-------------------------+---------------------------+--------------------------+------------------------+--------------+-------------+-------------+------------+-----------------------+
1 row in set (0.00 sec)
```

The last two columns are the important ones for now, so we look at them separately.

```sql
mysql> select table_name, referenced_table_name from information_schema.referential_constraints where constraint_schema = 'LARAVELDB';
+------------+-----------------------+
| TABLE_NAME | REFERENCED_TABLE_NAME |
+------------+-----------------------+
| books      | authors               |
+------------+-----------------------+
1 row in set (0.00 sec)
```

We see the table name that has the constraint and which table it refers to.

I have prepared two records one in each of the table like so.

```sql
mysql> select * from authors;
+----+---------------+---------------------+---------------------+
| id | name          | created_at          | updated_at          |
+----+---------------+---------------------+---------------------+
|  1 | Robert Jordan | 2020-12-24 18:16:35 | 2020-12-24 18:16:35 |
+----+---------------+---------------------+---------------------+
1 row in set (0.00 sec)
```

```sql
mysql> select * from books;
+----+----------------------+-----------+---------------------+---------------------+
| id | title                | author_id | created_at          | updated_at          |
+----+----------------------+-----------+---------------------+---------------------+
|  1 | The Eye Of The World |         1 | 2020-12-24 18:16:15 | 2020-12-24 18:16:15 |
+----+----------------------+-----------+---------------------+---------------------+
1 row in set (0.00 sec)
```

Now if I want to delete the author I will run into the troublesome child.

```sql
ERROR 1451 (23000): Cannot delete or update a parent row: a foreign key constraint fails 
(`laraveldb`.`books`, CONSTRAINT `books_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`))
```

One way to solve this is to delete the child record manually first then delete the parent record. 
However, there is a better way to handle this.

If we run this query 

```sql
select table_name, referenced_table_name, delete_rule from information_schema.referential_constraints
where constraint_schema = 'LARAVELDB';
```

We will get another column that will provide us with some clarity.

```sql
+------------+-----------------------+-------------+
| table_name | referenced_table_name | delete_rule |
+------------+-----------------------+-------------+
| books      | authors               | RESTRICT    |
+------------+-----------------------+-------------+
1 row in set (0.00 sec)
```

We have one delete rule that says restrict that is preventing us from deleting the author. 
We can add a cascading effect in our migration.

If we change our migration for the books' table like so

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->unsignedBigInteger('author_id');
    $table->timestamps();
    $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
});
```

And we run the 

```sql
SELECT table_name, referenced_table_name, delete_rule 
FROM information_schema.referential_constraints 
WHERE constraint_schema = 'LARAVELDB';
```

Again we will see that our delete rule has changed.

```sql
+------------+-----------------------+-------------+
| table_name | referenced_table_name | delete_rule |
+------------+-----------------------+-------------+
| books      | authors               | CASCADE     |
+------------+-----------------------+-------------+
1 row in set (0.01 sec)
```

So now if we try to delete the author the book will be deleted as well.

```sql
mysql> select * from authors;
+----+---------------+---------------------+---------------------+
| id | name          | created_at          | updated_at          |
+----+---------------+---------------------+---------------------+
|  1 | Robert Jordan | 2020-12-24 18:16:35 | 2020-12-24 18:16:35 |
+----+---------------+---------------------+---------------------+
1 row in set (0.00 sec)
```

```sql
mysql> select * from books;
+----+----------------------+-----------+---------------------+---------------------+
| id | title                | author_id | created_at          | updated_at          |
+----+----------------------+-----------+---------------------+---------------------+
|  1 | The Eye Of The World |         1 | 2020-12-24 18:16:15 | 2020-12-24 18:16:15 |
+----+----------------------+-----------+---------------------+---------------------+
1 row in set (0.00 sec)
```

Running this

```sql
delete from authors where id = 1;
```

Will give us this response.

```sql
mysql> delete from authors where id = 1;
Query OK, 1 row affected (0.00 sec)
```

And both our tables are empty

```sql
mysql> select * from authors;
Empty set (0.00 sec)
```

```sql
mysql> select * from books;
Empty set (0.00 sec)
```

## The refusal to add or update AKA ERROR 1452 (23000): Cannot add or update a child row: <a id="the-refusal-to-add-or-update-aka-error-1452"></a>

This error is a distant cousin to the previous error. In this case the foreign key is preventing us from inserting a record 
in the books' table with an author_id that does not exist in the authors table.

```sql
ERROR 1452 (23000): Cannot add or update a child row: a foreign key constraint fails 
`laraveldb`.`books`, CONSTRAINT `books_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE)
```

This error might not be that common in Laravel, but I think it's good to know what causes it and how to solve it.

Take this piece of php code in the store method of our Book Controller.

```php
$book = new Book();
$book->title = 'The Wizards First Rule';
$book->author_id = 2;
$book->save();
```

So the error above tells us that a foreign key constraint fails. That tells us that there is something with our constraint.
If we take a look att our authors table, and it's contents we will see this.

```sql
mysql> select * from authors;
+----+---------------+------------+------------+
| id | name          | created_at | updated_at |
+----+---------------+------------+------------+
|  1 | Robert Jordan | NULL       | NULL       |
+----+---------------+------------+------------+
1 row in set (0.00 sec)
```

Now since we are using a foreign key constraint it means that our foreign key must exist as a primary 
key in our authors table.
To solve this issue we need either to change the author_id in our insert or create a new author.

So if we create the author first

```php
$author = Author::create(['Terry Goodkind']);
```

Then we can create the book.

```php
$book = new Book();
$book->title = 'The Wizards First Rule';
$book->author_id = $author->id;
$book->save();
```

This error is best avoided by using the exists validation rule before trying to insert a new record.

```php
'author_id' => 'required|exists:authors,id'
```

## The irksome null value AKA SQLSTATE[23000]: Integrity constraint violation: <a id="the-irksome-null-value-aka-error-1048"></a>

The full error message might look like this.

```sql
SQLSTATE[23000]: Integrity constraint violation: 
1048 Column 'title' cannot be null 
(SQL: insert into `books` (`title`, `author_id`, `updated_at`, `created_at`) 
values (?, 2, 2020-12-24 21:05:43, 2020-12-24 21:05:43))
```

This error simply means that you are trying to insert a null value into the given column. 
You can see that quite clearly if you look at the SQL code in the error message.

```sql
(SQL: insert into `books` (`title`, `author_id`, `updated_at`, `created_at`) 
values (?, 2, 2020-12-24 21:05:43, 2020-12-24 21:05:43))
```
The title in this case should not be null and in the error message a null value is represented by a question mark.
Sometimes it might be OK to have a null value then you should add `->nullable()` to your migration.

As you can see demonstrated here

```php
$table->string('series')->nullable();
```

As a rule of thumb foreign key should almost never be nullable since it defeats the purpose of a foreign key.

So the easiest way to not get this kind of error is to validate the data before you try to insert it.

```php
'title' => 'required'
```

### The missing default value AKA SQLSTATE[HY000]: General error: 1364 Field <field> doesn't have a default value <a id="the-missing-default-value-aka-sqlstate-hy000"></a>

This issue has to do with null being passed to a date field that isn't nullable.

```sql
SQLSTATE[HY000]: General error: 1364 Field 'published_at' doesn't have a default value
(SQL: insert into `books` (`title`, `author_id`, `pages`, `updated_at`, `created_at`)
values (The eye, 2, 10, 2020-12-25 13:41:56, 2020-12-25 13:41:56))
```
There are a few ways to get around this issue.

* Make the field nullable 

```php
$table->date('published_at')->nullable();
```

* Add a default value to the migration

```php
$table->date('published_at')->default(Carbon::now());
```

And the mandatory validation of the field.

```php
'published_at' => 'required'
```


### The truncated value AKA SQLSTATE[22001]: String data, right truncated: <a id="the-truncated-value-aka-sqlstate-22001"></a>

To illustrate this issue we make a small change in our books' migration.

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('title', 10);
    $table->string('series')->nullable();
    $table->unsignedBigInteger('author_id');
    $table->timestamps();
    $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
});
```
We but a size limit of ten characters on the title field. 
If we then try to insert a title longer than ten characters into our table the 
database will throw this kind of error message.

```sql
SQLSTATE[22001]: String data, right truncated: 1406
 Data too long for column 'title' at row 1 
 (SQL: insert into `books` (`title`, `author_id`, `updated_at`, `created_at`)
 values (The eye of the world, 2, 2020-12-25 13:19:24, 2020-12-25 13:19:24))
```

This one is pretty straight forward and tells us the value is too long for title. 
This error is easily avoidable by validating the length of the value passed.

```php
'title' => 'max:10'
```

If you want to allow the user to insert longer values increase the length of the field.

### The value issue AKA SQLSTATE[HY000]: General error: 1366 Incorrect <type> value: <a id="the-value-issue-aka-sqlstate-hy000"></a>

This error occurs when you try to insert a value of incorrect type. 
Let's say that you have a numeric field in your database and the user tries to submit the string 
`ten` instead ot the numeric value `10`.  

```sql
SQLSTATE[HY000]: General error: 1366 
Incorrect integer value: 'ten' for column 'pages' at row 1 
(SQL: insert into `books` (`title`, `author_id`, `pages`, `updated_at`, `created_at`) 
values (The eye, 2, ten, 2020-12-25 13:31:39, 2020-12-25 13:31:39))
```

This can once again be avoided with the correct validation rules. 
In this case a simple check that the value is numeric will suffice.

```php
'pages' => 'numeric'
```

Good luck with your application,
Tray2
