---
title: Database Design (Part 2)
slug: database-design-part-2
summary: This is a continuation of my earlier post on Database Design. This time I will talk about some nuts and bolts in a database, and how many of them can help, and sometimes even break your application
created_at: 2022-02-26
updated_at: 2022-02-26
published_at: 2022-02-26
author: Tray2
---

This is a continuation of my earlier post on Database Design.
This time I will talk about some nuts and bolts in a database,
and how many of them can help, and sometimes even break your application.

I will cover the following topics.

* Data Types
* Indexes
* Joins
* Unions
* Views
* Materialized Views
* Federated Tables
* One-to-many With Different Properties
* Storing Currency
* Laravel Tips and Tricks
  

## Data Types

I will not go into too many details on data types, however we will take a closer
look at a few of them

* `CHAR` vs `VARCHAR`
* `VARCHAR` vs `TEXT`
* `INT` vs `UNSIGNED INT`
* `DATETIME` vs `TIMESTAMP`
* `BLOB`

### `CHAR` vs `VARCHAR`

If you read my previous [post](https://tray2.se/posts/database-design), you might
have noticed that I used `VARCHAR(255)` on many of the fields. The main reason for this is that Laravel does this by 
default when you define a column as string, and it doesn't really matter since a `VARCHAR` only uses as much space as 
it needs up to those 255 characters. That means that when you define a `VARCHAR(255)` and only inserts four characters
into it, it will only take up the space of four characters.

However, it would be better to set a more appropriate size on the fields.

Take this table as an example

```sql
CREATE TABLE tracks(
   id int auto_increment primary key,
   track_no int,
   title varchar(255),
   mix varchar(255),
   length varchar(255),
   record_id int,
   created_at timestamp,
   updated_at timestamp,
   FOREIGN KEY (record_id) REFERENCES records(id)
       ON DELETE CASCADE
);
```
 
We have the track length as `VARCHAR(255)`, so we could theoretically get values longer than needed. 
We can decide that the format should be `HH:MM:SS` or maybe just `MM:SS` since I don't think there is a song in 
the world that is longer than 99 minutes, so if we go with the latter we could set a lower limit on the
amount of characters that we allow. 

```sql
length varchar(5),
```

In a Laravel migration it would be.

```php
$table->string('length', 5);
```

The `VAR` in `VARCHAR` stands for `variable`, meaning that it will adapt to the size of its content. 
A `CHAR` on the other hand is of a fixed length. So if you set the column type to `CHAR(10)` it will always 
take up the space of ten characters regardless of the string's length (as long as it's less than or equal to ten characters).

I recommend sticking to `VARCHAR` for simplicity's sake.

### `VARCHAR` vs `TEXT`

Like `VARCHAR`, the `TEXT` data type is used to store combinations of letters and numbers. However, the `TEXT` type
just like `INT` comes in different sizes. Those sizes are `TINY`, `TEXT`, `MEDIUM` and `LONG`.
Unlike a `CHAR` or `VARCHAR` a `TEXT` column isn't stored in the database memory so every time you want to read it,
the database needs to fetch it from disk, thus giving it a small decrease in performance.

You can store crazy amounts of characters in the different text types.

* `TINY` = 255
* `TEXT` = 64KB
* `MEDIUM` = 16MB
* `LONG` = 4GB

If you have a `TINYTEXT` column consider changing it to a `VARCHAR(255)` instead, it will give you a tiny speed boost.
There are some more small differences as well but nothing that we need to concern us about unless of course you're curious.

My personal recommendation is to never use TINY, MEDIUM or LONG in your columns. The reason for that is, that it
slows your database down, and it is better to store that amount of data on the file system.

### `Int` vs `Unsigned Int`

`Int` comes in many flavors, `Tiny`, `Small`, `Medium`, `Int` and `Big`. In my SQL examples I've used `INT` for both the primary
and all foreign keys, while Laravel since version 5.8 uses `BIGINT` as the default. In most cases it doesn't even 
matter which you use since a regular `INT` can handle values up to `2147483647` and `BIGINT` up to `9223372036854775807`. 

Since I used just INT it can contain a value between `-2147483648` and `2147483647`
which is all well and good, however our primary and foreign keys can never be negative. 
That means that we "loose" quite a few numbers. To remedy this we can add another modifier to 
our column definition, `UNSIGNED`. The unsigned tells the database that the column can't have any negative value 
and thus increasing the upper limit for an INT to `4294967295`

 **Remember**
> The important thing here is that the data type of both the id on the records table
> and the record_id in the `tracks` table are  exactly the same, or you will get an
> error when creating the tracks table. 
> You must also create 
> the `records` table first otherwise you will get an error while creating the tracks
> table.

```sql
CREATE TABLE tracks(
   id int unsigned auto_increment primary key,
   track_no int unsigned,
   title varchar(255),
   mix varchar(255),
   length varchar(255),
   record_id int unsigned,
   created_at timestamp,
   updated_at timestamp,
   FOREIGN KEY (record_id) REFERENCES records(id)
       ON DELETE CASCADE
);
```

In Laravel most of this is already handled for you since we used the `->id` and the `->foreignId` methods. 
If we have a column of some kind of INT, and we want it unsigned, we can either add `->unsigned()`
to the definition or change the int to `unsignedInt()`. I prefer the second approach.

```php
   $table->id();
   $table->unsignedInt('track_no');
   $table->string('title');
   $table->string('mix');
   $table->string('length');
   $table->foreignId('record_id')
     ->constrained('records')
     ->onDelete('cascade');
   $table->timestamps();
``` 

The data type used by Laravel for id and foreignId is `UNSIGNED BIGINT`, making
the upper limit a mind-blowing `18446744073709551615`. I'm not even going to try to figure out how much that is, 
I'll settle with a "shitload". 

We do however have an `INT` column that we probably should modify a bit. The `track_no` is an `INT` and in the examples
I've set it to unsigned. There is no record in the world that comes even close to that many tracks, so I think it's 
a good idea to change the data type to `TINYINT` and `UNSIGNED`.
That lowers the max capacity to 255.

```sql
track_no tinyint unsigned,
``` 

```php
$table->unsignedTinyInt('track_no');
```

### `DATETIME` vs `TIMESTAMP`

The biggest difference between these are how far back in time you can go.
`DATETIME` can be set to the first of January of year 1000 while `TIMESTAMP` start from the first of January 1970. 
`TIMESTAMP` has an upper limit of 2038 while `DATETIME` has the upper limit of 9999. The `TIMESTAMP` has a higher precision,
and takes consideration of the current timezone while `DATETIME` doesn't.

So what happens with `TIMESTAMP` after the 19th of January 2038, well hopefully this has been solved by the 
MySQL/MariaDB developers, if it isn't switch to `DATETIME` format instead.

### `BLOB`

What the hell is a `BLOB`?

It stands for Binary Large Object, and it's a way to store binary files like images and Word documents 
inside your database tables.

This is a very bad practice in my opinion. It will slow down your database so please don't use any `BLOBS` to store 
uploaded binary files, use the file system for that.

### What about the other types?

There are a few other data types as well, but for the sake of not making this post TLDR, I will skip them for now.

## Indexes

Oh boy, indexes, as LeighMac would have said, "Now we're cooking with gas". Indexes are a bit like communication,
it's one of the easiest things to do thus making it so easy to screw up. A wrongly created index is like saying
something to a person that you are dating, who then interprets it in a way you never intended.

Enough of the bad comparisons, let's get down to business.

An index helps the database to perform a search in the given table.

Consider this query.

```sql
SELECT *
FROM table1
WHERE value = 1001
```

If we then have a million records with values between 1 and 10000 in a random order in our table. We would have to go 
through those million records to find the ones that match our criteria, that is called a full table scan. Full table 
scans are not necessarily a bad thing if you don't have that many records in your table, but in our case it's quite bad.

So by making an index we can help the database to find the matching records faster.
Here is how we can make an index

```sql
CREATE INDEX table1_value_idx ON table1 (value);
```

Laravel Migration

```php
$table->integer('value')->index();

// Or

$table->index('value');
```

Now what this does, is making something that we can compare to the table of contents in a book or the index that
you have in the back of most computer science books. An alphabetical list of words and phrases.

We have a list of values like this.

1. 12
2. 15
3. 1001
4. 9000
5. 1568
6. 13344
7. ...

We create an index it would look something like this in very simplified terms.

```
1 - 999                      1000 - 1999
1 - 99 | ... | 900 - 999 |   1000 - 1099 | ...  | 1500 - 1599 | ...   
12                           1001                 1568  
15
```

So what the index does is to compare the value to the highest level, and if we go with our value 1001, it will look
at the first peak (1 - 999) and compare it. 1001 does not match the value in this tree, then it will check the second
peak (1000 - 1999) and here it will match, so it will go down into that tree and look at the next peak, is it between
1000 and 1001, yes it is, so it will compare those 99 records. So instead of doing that comparison one million times,
it does it about 100 times.

Then you might think, "I want to search on all the columns, and I need it to be super-fast. 
I'll add an index to all the columns".

Well you can do that but be aware that every index that you create makes the other operations slower since it needs
to update the indexes as well. That makes it a bit of a trade-off between fast retrieval and slower inserts and updates. 
A wrongly created index might also make the retrieval slower as well. The database doesn't know which to use thus 
making the query slow when it chooses the wrong one(s). There is some other caveats as well when it comes to indexes, 
but that is something that I won't get into the details about.

I can however briefly go into something that happened to a member on Laracasts. The default setting for how
Laravel stores queued job is sync, but this person wanted to use a MySQL database to store the jobs, which is fine.
However, the `jobs` table that stores all the queued jobs has one index on the queue column and the normal primary
key index. This is all fine and well but the person queued up 100K (100000) jobs which each took less than second
to finish, that added with that he was using multiple workers to process the queue. So in short his application updated
the `jobs` table hundreds of times each second, which caused the database to update the indexes the same amount
of times every second. Every time an index is updated it locks the table and since it was updating very rapidly the 
database had to wait for the previous update to finish, and when it gets bad enough it causes something called a 
deadlock. It can be compared to a rush hour gridlock. The different RDBMS have their own way how they handle this, 
for example an Oracle database would not have deadlocks due to updating indexes. Not sure on how MSSQL and 
Postgres handles this.

So imagine the above scenario with a table that has ten columns and each of them has an index. The likely-hood of
it causing a deadlock on a high transaction table is quite big. 

Let's move on with more fun index stuff. I will not be able to cover everything since it would take thousands of pages,
but I feel the need to cover some things that are important to understand. 

Consider this query.

```sql
SELECT *
FROM table1
WHERE last_name = 'Jordan'
AND first_name = 'Robert'
AND created_at BETWEEN '1990-05-02' AND '2015-05-03';
```

Now we use three columns in our `WHERE` clause, should we then add three indexes or should we combine them into a composite index? 
Composite? Yes, composite. It means that you combine columns in your index. So in the simple case above we would
**probably** be best of with creating a composite index with the first and last name, like this.

```sql
CREATE index table1_name_idx ON table1 (last_name, first_name);
```

In Laravel

```php
$table->index(['last_name', 'first_name']);
```

Be aware that the order of the composite index matter, if it's the wrong order it might not even be used by your query.

Notice that I wrote **probably**, it's never a good idea to just add an index, you need to analyze the query and
how it is executed before making the decision to add an index.

How do you know if the index is used or if it's really needed?
The best trick is to use `EXPLAIN` and I will explain it to you (yes, pun intended).

The `EXPLAIN` keyword runs the query and the database returns a detailed plan on how it gets the result you have requested. 
So for our query it might look like this without any indexes.

So I have prepared like a good Tv-chef, and made a `books` table without any indexes other than the primary key one.

```bash
MariaDB [mediabase]> desc books;
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| title      | varchar(255)        | NO   |     | NULL    |                |
| series     | varchar(255)        | NO   |     | NULL    |                |
| part       | int(10) unsigned    | YES  |     | NULL    |                |
| format_id  | bigint(20) unsigned | NO   | MUL | NULL    |                |
| genre_id   | bigint(20) unsigned | NO   | MUL | NULL    |                |
| isbn       | varchar(255)        | NO   |     | NULL    |                |
| released   | int(10) unsigned    | NO   |     | NULL    |                |
| reprinted  | int(10) unsigned    | YES  |     | NULL    |                |
| pages      | int(10) unsigned    | NO   |     | NULL    |                |
| blurb      | text                | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
13 rows in set (0.008 sec)
```

I have also seeded it with 58003581 books, that is 58 Million books for the ones that doesn't want to count the digits.
It's all dummy data, but we will play around with a couple of queries and see the result.

Let's start with a simple primary key search.

```sql
SELECT *
FROM books
WHERE id = 254554:
```

This query is similar to doing this in Laravel,

```php
Books::where('id', 254554)->get();
```

Now this query took 0.00 sec in the database, how come?

Let's use EXPLAIN on the query.

```sql
EXPLAIN
SELECT *
FROM books
WHERE id = 254554:
```

We get this execution plan, which tells us what the database needs to do to retrieve the result of our
query. These will take some time to learn how to interpret, and is something that I can't cover fully in a blog post, we will however scratch the surface.

```bash
MariaDB [mediabase]> explain select *  from books where id = 254554;
+------+-------------+-------+-------+---------------+---------+---------+-------+------+-------+
| id   | select_type | table | type  | possible_keys | key     | key_len | ref   | rows | Extra |
+------+-------------+-------+-------+---------------+---------+---------+-------+------+-------+
|    1 | SIMPLE      | books | const | PRIMARY       | PRIMARY | 8       | const | 1    |       |
+------+-------------+-------+-------+---------------+---------+---------+-------+------+-------+
1 row in set (0.000 sec)
```

The two interesting things we see here, is that the `possible_key` is `PRIMARY` and that the `key` is the one actually
used and in this case it's the primary key. The `rows` column's interesting, since it tells you how many rows
the query had to look at before finding the one we wanted.

We can't really improve this query in any way, but if we hadn't told the database that the `id` is the primary key we would
have seen a quite different result, and that brings us to our next query.

```sql
SELECT * 
FROM books
WHERE title = 'Est';
```

Now this query took 37.543 seconds to complete and gave a result of 1164081 records matching our query.
37 seconds is way too long, so let's run our query with  `EXPLAIN`.

```sql
EXPLAIN
SELECT * 
FROM books
WHERE title = 'Est';
```

And this is the execution plan we get.

```bash
MariaDB [mediabase]> explain select *  from books where title  = 'Est';
+------+-------------+-------+------+---------------+------+---------+------+----------+-------------+
| id   | select_type | table | type | possible_keys | key  | key_len | ref  | rows     | Extra       |
+------+-------------+-------+------+---------------+------+---------+------+----------+-------------+
|    1 | SIMPLE      | books | ALL  | NULL          | NULL | NULL    | NULL | 53689936 | Using where |
+------+-------------+-------+------+---------------+------+---------+------+----------+-------------+
1 row in set (0.000 sec)
```

So let's start from the left. The `type` is ALL compared to the CONST we had in our previous query, this is an indication 
that the database needs to look at all the records in the table. The `possible_keys` and the `key` is null and that
tells us that no index has been used in the execution of the query, the `rows` column tells us how many records it has 
looked at.

Now, what will happen if we add an index to the title column?

```sql
CREATE index books_title_idx ON books (title);
```

Now this will take some time to process depending on how many records you have in the database.

```bash
MariaDB [mediabase]> CREATE index books_title_idx ON books (title);

Query OK, 0 rows affected (2 min 13.640 sec)
Records: 0  Duplicates: 0  Warnings: 0
```

In my case it took just over two minutes.

Now lets run our query again without the `EXPLAIN` and see what happens.

```sql
SELECT * 
FROM books
WHERE title = 'Est';
```

```bash
1164081 rows in set (6.373 sec)
```

Holy crap, we decreased the execution time with over 30 seconds. While this is a huge improvement, we still should never
run this kind of query in production, we would paginate the result or chunk it into smaller pieces. However, it does
demonstrate the power of an index and the power of the database. This query gives a result of 1.1 Million records, and 
for that amount of records pulled out of 58 Million records it is to be considered fast.

So let's take a look at our execution plan.

```bash
MariaDB [mediabase]> explain select * from books where title  = 'Est';
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+-----------------------+
| id   | select_type | table | type | possible_keys   | key             | key_len | ref   | rows    | Extra                 |
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+-----------------------+
|    1 | SIMPLE      | books | ref  | books_title_idx | books_title_idx | 1022    | const | 2417788 | Using index condition |
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+-----------------------+
1 row in set (0.000 sec)
```

Now we see that the `type` has changed from ALL to `ref` which is good, and we see that the index we created is the
`possible_keys` and that the `key` is that index. We can also see that the number of `rows` has decreased by more than
51 million rows. The `extra` column informs us that the query is using index condition which is good.

So what if we need to search on two columns?

Well let's take a look.

```sql
SELECT * 
FROM books
WHERE title = 'Est';
AND created_at = '2022-01-06 13:18:28'
```

It gives us this.

```bash
19 rows in set (4.736 sec)
```

Which is very slow, by just looking at the execution time you might think that it's faster than the previous example of
just above six second. You need to consider the amount of records fetched as well, in this case 19 vs 1.1 Million, so let's 
see that the execution plan tells us.

```bash
MariaDB [mediabase]> EXPLAIN SELECT *  FROM books WHERE title = 'Est' AND created_at = '2022-01-07 03:46:46';
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+------------------------------------+
| id   | select_type | table | type | possible_keys   | key             | key_len | ref   | rows    | Extra                              |
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+------------------------------------+
|    1 | SIMPLE      | books | ref  | books_title_idx | books_title_idx | 1022    | const | 2417788 | Using index condition; Using where |
+------+-------------+-------+------+-----------------+-----------------+---------+-------+---------+------------------------------------+
```

We still see that it uses the books_title_index, but if we look in the `Extra` column we see that it is also using a 
where and that is probably what slows the query down. 

Now we try to add another index on the created_at column. 

```sql
CREATE index books_created_at_idx ON books (created_at);
```

That took about a minute to complete.

```bash
MariaDB [mediabase]> CREATE index books_created_at_idx ON books (created_at);
Query OK, 0 rows affected (1 min 0.712 sec)
Records: 0  Duplicates: 0  Warnings: 0
```

So let's run our query again and see the result.

```bash
19 rows in set (0.010 sec)
```

Wow, that is not bad at all. Now this would be fine if our app only had those search criteria, but that is almost 
never the case now is it, what if we wanted to search for the `updated_at` column together with the title, well
then we would be back to square two, with the query only using one of the indexes.

We could of course add another index, but we would be getting close to that breaking point where the index does more
harm than good. So why don't we drop the indexes that we created and make a new one that is a bit different.

```sql
DROP index books_title_idx ON books;
DROP index books_created_at_idx ON books;
```

In a Laravel migration it would look like this.

```php
Schema::table('books', function (Blueprint $table) {
  $table->dropIndex(['title']);
  $table->dropIndex(['created_at']);
});
```

```bash
MariaDB [mediabase]> drop index books_title_idx on books;
Query OK, 0 rows affected (0.021 sec)
Records: 0  Duplicates: 0  Warnings: 0

MariaDB [mediabase]> drop index books_created_at_idx on books;
Query OK, 0 rows affected (0.018 sec)
Records: 0  Duplicates: 0  Warnings: 0
```

Now let's create something called a composite index, that is an index that has more than one column. 
The syntax is the same as a regular index, but we add the additional columns seperated by a comma.

```sql
CREATE index books_title_created_at_idx ON books (title, created_at);
```

and in Laravel it would do something like this.

```php
$table->index(['title', 'created_at']);
```

One thing to be aware of when generating indexes in Laravel is that you might just get an index name that is too long,
and to solve that issue you can pass a second argument to the index method where you give it the desired index name.

```php
$table->index(['title', 'created_at'], 'book_tit_cre_idx');
```

So let's create our composite index and see how our database likes that.

```bash
MariaDB [mediabase]> CREATE index books_title_created_at_idx ON books (title, created_at);
Query OK, 0 rows affected (2 min 23.218 sec)
Records: 0  Duplicates: 0  Warnings: 0
```

It took a while to create the index, but that was expected. So let's run both our queries and see the result.

```sql
SELECT * 
FROM books
WHERE title = 'Est';
```

Not bad for 1.1 million records

```bash
1164081 rows in set (6.131 sec)
```

We run `EXPLAIN` on it.

```bash
MariaDB [mediabase]> explain select * from books where title = 'Est';
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------+---------+-----------------------+
| id   | select_type | table | type | possible_keys              | key                        | key_len | ref   | rows    | Extra                 |
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------+---------+-----------------------+
|    1 | SIMPLE      | books | ref  | books_title_created_at_idx | books_title_created_at_idx | 1022    | const | 2164592 | Using index condition |
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------+---------+-----------------------+
1 row in set (0.000 sec)
```

We get almost the same result as before when we had a separate index on the title.

How will our two condition query run with the new index?

```sql
SELECT * 
FROM books
WHERE title = 'Est';
AND created_at = '2022-01-06 13:18:28'
```

Well, even more wow, I'd say.

```bash
19 rows in set (0.000 sec)
```

So let's take a look at the execution plan.

```bash
MariaDB [mediabase]> explain select * from books where title = 'Est' and created_at = '2022-01-06 13:18:28';
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------------+------+-----------------------+
| id   | select_type | table | type | possible_keys              | key                        | key_len | ref         | rows | Extra                 |
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------------+------+-----------------------+
|    1 | SIMPLE      | books | ref  | books_title_created_at_idx | books_title_created_at_idx | 1027    | const,const | 19   | Using index condition |
+------+-------------+-------+------+----------------------------+----------------------------+---------+-------------+------+-----------------------+
1 row in set (0.000 sec)
```

I think we did a good job on this one. However, make sure that you need them before adding them.

If the table is pretty static and doesn't change much, then I think it's OK to be liberal with indexes, but be aware
that the database might just choose one that isn't optimal for the query you are trying to run.

### Other indexes

There are a few other types of indexes as well, and I will go through them briefly.

We have mentioned `PRIMARY` index and the simple, regular or normal index. 
There are three other types of indexes, `UNIQUE`, `FULLTEXT` and `DESCENDING`.

The `UNIQUE` index isn't used for searching like the ones we talked about earlier, they add a constraint to the table.
A constraint means that it places certain rules on the data in the table and the `UNIQUE` index or constraint makes sure
that only one record can have a certain value in the indexed column. This is similar to the `PRIMARY` index which also
is unique. The `UNIQUE` index like a normal index can be composite of several columns. This is also true of the `PRIMARY`
index. When using a `UNIQUE` index you can with ease use [`upserts`](https://laravel.com/docs/9.x/eloquent#upserts) 
in Laravel

How do we create a `UNIQUE` index?

```sql
CREATE UNIQUE INDEX index_name
ON table_name(index_column_1);
```

In Laravel, you use the unique method in your migration.

```php
$table->string('email')->unique();
```

The `FULLTEXT` index is used for tables where you want to use full text search. The full text search stores words and
groups of words in the index. `FULLTEXT` index has two different syntax for creation.

```sql
CREATE TABLE table_name(
    column_list,
    ...,
    FULLTEXT (column1,column2,..)
);
```

If you want to add it after the table is created you use `ALTER TABLE` syntax.

```sql
ALTER TABLE table_name  
ADD FULLTEXT(column_name1, column_name2,…)
```

You can also use the regular create syntax.

```sql
CREATE FULLTEXT INDEX index_name
ON table_name(idx_column_name,...)
```

In Laravel, you do this

```php
$table->fulltext('body');
```

To drop the index you use the following command.

```sql
ALTER TABLE table_name
DROP INDEX index_name;
```

This is good for searching for words over multiple columns at once, and you can't just do something like this that you normally do when doing filtering.

```sql
SELECT *
FROM table_name
WHERE text LIKE '%Laravel%';
```

You need to use MATCH syntax like so.

```sql
SELECT * FROM table_name WHERE MATCH(col1, col2)
AGAINST('search terms' IN NATURAL LANGUAGE MODE);
```

The query above will search for the `search terms` in both col1 and col2 at the same time.

The support for FULLTEXT search in Laravel has been a bit limited, but the support has improved in Laravel 9. 

To do a full text search in Laravel 9 you simply use the `whereFullText()` and/or `orWhereFullText()` methods. At the
time of writing they only supports MySQL/MariaDB and Postgres.

```php
$table = DB::table('table')
           ->whereFullText(['col1', 'col2'], 'search terms')
           ->get();
```

I suggest reading about it in the [Docs](https://laravel.com/docs/9.x/queries#full-text-where-clauses).

For earlier versions of Laravel you can use a package like the 
[swisnl/laravel-fulltext](https://packagist.org/packages/swisnl/laravel-fulltext) to improve the support.

The next index is quite new to MySQL as it was added by Oracle in version 8. They call it a descending index, it's 
basically the same thing as a normal index, but it is stored in reverse, and is said to be good for tables that you
need to get the latest created records. You create the descending index the same way you do a normal index with one 
small difference, you add the one more parameter for each column in the columns list. 

```sql
CREATE index books_reverse_created_at_idx ON books (created_at desc);
```

Partial index or Filtered indexes is not supported by MySQL yet, they are however supported by the Postgres RDBMS.
What it does is that it takes a part of the table that has a certain value, let's say users with an active subscription.
The syntax for this is as follows

```sql
CREATE INDEX active_subscriptions
          ON users (username)
       WHERE active_subscription = 'Y'
```

`PARTIAL`  indexes can be faster than a regular "full" index, it depends on how many records that has the indexed criteria.
I have not used it but if you are using Postgres or MSSql you should read up on them.

There is one more index type `SPATIAL` index, but I will not cover it here since I never used it, and I don't
think that many Laravel applications will need to use it either.

## Joins

Joins is one of the most important things in a relational database. It's the glue that links the tables together.
These links usually also has a constraint connected to them. We talked about that in the first post on database
design. So far we have only worked with the table books, and if we look at it, we see that we have some foreign keys.

```bash
MariaDB [mediabase]> desc books;
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| title      | varchar(255)        | NO   | MUL | NULL    |                |
| series     | varchar(255)        | NO   |     | NULL    |                |
| part       | int(10) unsigned    | YES  |     | NULL    |                |
| format_id  | bigint(20) unsigned | NO   | MUL | NULL    |                |
| genre_id   | bigint(20) unsigned | NO   | MUL | NULL    |                |
| isbn       | varchar(255)        | NO   |     | NULL    |                |
| released   | int(10) unsigned    | NO   |     | NULL    |                |
| reprinted  | int(10) unsigned    | YES  |     | NULL    |                |
| pages      | int(10) unsigned    | NO   |     | NULL    |                |
| blurb      | text                | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
13 rows in set (0.008 sec)
```

Let's zoom in for a closer look

```bash
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| format_id  | bigint(20) unsigned | NO   | MUL | NULL    |                |
| genre_id   | bigint(20) unsigned | NO   | MUL | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
```

Both the `format_id` and the `genre_id` is a reference to another table. A foreign key best practice is to follow
the naming convention of the table name that it references in singular for followed by an underscore and the name of 
the column it references. So the `format_id` is connected to the `formats` table, and it's `id` column, but you know this
already if you read the post about database design.

With that underway we can make our first join. There are a few different types and there are different syntax's  
as well, but we will try to go through them.

There are three types of joins that we can choose from.

* Inner join
* Outer join
* Cross join

Inner join is used to get all the records from that matches up on the given condition. 

```sql
SELECT b.*, f.format
FROM books b
[INNER] JOIN formats f
ON b.format_id = f.id;
```

The `INNER` key word isn't strictly necessary and can be omitted if preferred. 

There is also an alternative older syntax that I am much more fluent with and that looks like this.

```sql
SELECT b.*, f.format
FROM books b,
     formats f
WHERE b.format_id = f.id;
```

Which one you use is up to you in your own projects, but if you work for a company that has a standard adhere to it.

Now in Laravel there are way more than two syntax versions to achieve this, but I will demonstrate the Query builder 
version and the Eloquent version, keep in mind that I'm in no way an Eloquent expert and there might be better ways
to write the queries.

Query builder

```php
DB::table('books')
    ->join('formats', 'books.format_id', '=', 'formats.id')
    ->select('books.*', 'formats.format')
    ->get();
```

Eloquent

```php
Book::with('formats')->get();
```

Eloquent has the nicest syntax of them all, but it does require that you have the proper relationship
setup in your Book model, but there is also a small difference between the queries, and that is that the eloquent one takes
all columns from the formats table. You will need to use some not so Eloquent code to just take one column.

However, we are not supposed to talk about the ins and outs of Eloquent, we are talking about joins.

Outer joins are similar to inner joins, but they will also show records that doesn't have a corresponding record
in the joined in table. Now there are actually two versions of outer joins, left and right. 
They are quite similar but let's start with a left outer join.

```sql
SELECT b.*, r.review
FROM books b
LEFT [OUTER] JOIN reviews r
ON b.id = r.book_id;
```

The OUTER keyword is optional.

This query would give us all the books and the review attached to it. If there is no review that has the book_id,
the review column will be null. With an inner join, the books that don't have a review would not have been retrieved.

In the older way we would use a special sign on the join conditions and depending on which side it is on, tells if
it's a left or right join. However, that syntax has been deprecated in most of the RDBMS out there. The only one that
still supports the old syntax that I'm aware of is Oracle.

The right outer join is the opposite, it would show all reviews even if they don't belong to a book.

To do these kinds of joins in Laravel you swap the `join` method to the `leftJoin` or the `rightJoin` method.

```php
$users = DB::table('users')
->leftJoin('posts', 'users.id', '=', 'posts.user_id')
->get();

$users = DB::table('users')
->rightJoin('posts', 'users.id', '=', 'posts.user_id')
->get();
```

Cross joins or Cartesian joins can sometimes be useful but don't use them unless you know what you are doing.
!! **Use with caution** !!

```sql
SELECT columns
FROM tableA
CROSS JOIN tableB;
```

The problem with this is that you don't have any common key, so in our case with the books and the formats, 
we would get the same book listed for each of the formats.

The old way of writing this would be

```sql
SELECT columns
FROM tableA,
     tableB
```

## Unions

Unions is a way to join two queries together. Imagine that you have three tables, books, movies, records, 
and you want to get the ten last added items from those tables. I'm not talking about ten latest from every table,
I'm talking about the ten last added rows regardless of which of the tables they exist in.

We want the title, the type (book, movie or record) and the id of the row, the primary key if you will.
For this we need to use a union, and they come in two flavors, `UNION` and `UNION ALL`. The difference is that `UNION`
cares about duplicates while `UNION ALL` doesn't. That makes the `UNION ALL` a bit faster than `UNION`.

Now unions have some requirements, the number of columns must be the same from both queries and the data type also 
needs to similar. That means that an int and a big int are ok to mix since they both are whole numbers.

The first query decides what the columns will be named in the result, so you might need to use aliases to get
a good name for each column.

In our example we don't need to do that since a book, a movie and a record has a title. Now since we are using three
different tables we need to know from which table the row came from. We do that by adding a hardcoded value into each
of our queries.

Enough talk, let's get down to business and look at some code.

```sql
SELECT id, title, 'books' source
FROM books
UNION ALL
SELECT id, title, 'movies'
FROM movies
UNION ALL 
SELECT id, title, 'records'
FROM records;
```

In the first query, the one from the `books` table we give the hardcoded value of `books` the alias `source`,
you might notice that I didn't use the `AS` keyword, the reason for that is that it's optional and not required.
It comes down to a preference, if you want to use it or not, I prefer not to.

The query above will give us all the books, movies and records, so how do we sort them?
Well we wrap the query with another query, like this.

```sql
SELECT t.* 
FROM (SELECT id, title, created_at, 'books' source
      FROM books
      UNION ALL
      SELECT id, title, created_at, 'movies'
      FROM movies
      UNION ALL 
     SELECT id, title, created_at, 'records'
     FROM records) t
ORDER BY t.created_at DESC
LIMIT 10;
```
We use the union query as the `FROM` table, this is called an inline view, and that brings us right to the next topic
views, but first we look at the Laravel version of the union.

Unions in Laravel isn't really that nice to look at, but we give it a go anyway.

```php
$books = DB::table('books')
    ->select('id', 'title', 'created_at', DB::raw("'books' AS source"));
    
$movies = DB::table('movies')
    ->select('id', 'title', 'created_at', DB::raw("'movies' AS source"));

$latestTen = DB::table('records')
    ->select('id', 'title', 'created_at', DB::raw("'movies' AS source"));
    ->unionAll($books)
    ->unionAll($movies)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

Like I said, not a pretty sight.


## Views

Now views are not something that I think is that common when it comes to web development,
at least not in any of the tutorials that I've seen, and I've seen quite a few of them. Now what is a view?

Remember when we used an inline view in our union query, well a view is kind of like that, but it resides in the
database like a table, a read-only table if you like. So let's bring back the union code.

```sql
SELECT t.* 
FROM (SELECT id, title, created_at, 'books' source
      FROM books
      UNION ALL
      SELECT id, title, created_at, 'movies'
      FROM movies
      UNION ALL 
     SELECT id, title, created_at, 'records'
     FROM records) t
ORDER BY t.created_at DESC
LIMIT 10;
```

What if I said we can make that code look like this instead?

```sql
SELECT * 
FROM media_view
ORDER BY created_at DESC
LIMIT 10;
```

Well we can and this is how.

```sql
CREATE OR REPLACE VIEW media_view AS
SELECT id, title, created_at, 'books' source
FROM books
UNION ALL
SELECT id, title, created_at, 'movies'
FROM movies
UNION ALL
SELECT id, title, created_at, 'records'
FROM records;
```

What is the point of creating a view when you just as easily can run the full query. The point is that using a view is
like keeping our SQL `DRY`, so instead of repeating the same code over and over in several queries we can store it in the
database. Not only does it help us to keep our SQL queries `DRY`, it also makes the SQL we need to write much easier to
read. Another added benefit is that it enables us to use Eloquent rather than the Query builder.

While the example with the union is a real world example, we might need a more complex example.

We have the following tables.

```bash
+------------------------+
| Tables                 |
+------------------------+
| author_books           |
| authors                |
| books                  |
| formats                |
| genres                 |
| publishers             |
| series                 |
+------------------------+
```

The `author_books` table is the pivot table between `authors` and `books`, and yes I am aware that the Laravel convention
is `author_books`, but I prefer to keep my table names, even the pivot table names in plural form.

The `books` table has foreign keys for 

* formats
* genres
* publisher
* series

They all follow the foreign key naming convention, except series since it's the same regardless of singular
or plural, so I made a change there as well to `serie_id` to fit the over all naming scheme. This might be a bad
practice or unconventional, but it's a personal preference of mine. The thing you need to be aware of is that you
need to adapt your Eloquent relationships for any deviations you make from the Laravel convention.

I'm pretty confident that you know how to write a SQL query that uses multiple tables, but we will take it step
by step for those who don't know.

Let's start with the authors table. To be able to join the books and the authors tables we need to join the author_books
table as well.

```sql
SELECT a.name,
       b.title,
       b.published,
       b.part
FROM books b
JOIN author_books ab
ON b.id = ab.book_id
JOIN authors a
ON ab.author_id = a.id
```

Next up we need to join the other tables, they only need one join since the `books` table has a foreign key for them.

```sql
SELECT a.name,
       b.title,
       b.published,
       b.part,
       f.format,
       g.genre,
       p.publisher,
       s.series
FROM books b
JOIN author_books ab
ON b.id = ab.book_id
JOIN authors a
ON ab.author_id = a.id
JOIN formats f
ON b.format_id = f.id
JOIN genres g
ON b.genre_id = g.id
JOIN publishers p
ON b.publisher_id = p.id
JOIN series s
ON b.serie_id = s.id;
```

The syntax in the Query builder would be similar as the one we looked at in the join section. With some additional joins of course.

```php
DB::table('books')
    ->join('author_books', 'books.id', '=', 'author_books.book_id')
    ->join('authors', 'author_books.author_id', '=', 'authors.id')
    ->join('formats', 'books.format_id', '=', 'formats.id')
    ->join('genres', 'books.genre_id', '=', 'genre.id')
    ->join('publishers', 'books.publisher_id', '=', 'publishers.id')
    ->join('series', 'books.serie_id', '=', 'series.id')
```

In Eloquent we could just add the tables to the join like so.

```php
Book::with(['authors', 'formats', 'genres', 'publishers', 'series'])->get();
```

While the Eloquent way would all work fine if you don't care about what you pull from each table, but we do. 
So to make Eloquent still be Eloquent we can now get on with creating the view.

To create a view we use `CREATE OR REPLACE`, that means that it will overwrite any existing view with the same name as
the one we have given. Notice that I use the plural form of view, this is to make it
work with a Laravel model out of the box.

```sql
CREATE OR REPLACE VIEW book_views (
    name,
    title,
    published,
    part,
    format,
    genre,
    publisher,
    series
)
AS
SELECT a.name,
       b.title,
       b.published,
       b.part,
       f.format,
       g.genre,
       p.publisher,
       s.series
FROM books b
JOIN author_books ab
ON b.id = ab.book_id
JOIN authors a
ON ab.author_id = a.id
JOIN formats f
ON b.format_id = f.id
JOIN genres g
ON b.genre_id = g.id
JOIN publishers p
ON b.publisher_id = p.id
JOIN series s
ON b.serie_id = s.id;
```

**Caution, There is a huge difference between eager loaded and fetching from a view.**

The records returned from the view would look like they were fetched from a single table as you can see in the example
below.

```php
=> Illuminate\Database\Eloquent\Collection {#2102
     all: [
       App\Models\BookView {#2103
         author_id: "1",
         author_name: "Jordan, Robert",
         author_slug: "jordan-robert",
         id: 1,
         title: "Eye Of The World",
         title_slug: "eye-of-the-world",
         serie: "The Wheel Of Time",
         start_year: 1990,
         serie_slug: "the-wheel-of-time",
         part: 1,
         pages: 814,
         published: 1990,
         edition: "mass-market",
         edition_id: 5,
         publisher: "Tor",
         publisher_id: 4,
         format: "Pocket",
         format_id: 5,
         genre: "Fantasy",
         genre_id: 1,
         condition: "Mint",
         condition_id: 1,
         blurb: "The Wheel of Time turns and Ages come and pass. What was, what will be, and what is, may yet fall under the Shadow.",
       },
     ],
   }
```

While an eager loaded result would look something like this.

```php
=> Illuminate\Database\Eloquent\Collection {#2109
     all: [
       App\Models\Book {#2106
         id: 1,
         title: "The Eye Of The World",
         title_slug: "the-eye-of-the-world",
         serie_id: 1,
         part: 1,
         pages: 814,
         published: 1990,
         edition_id: 5,
         publisher_id: 4,
         format_id: 5,
         genre_id: 1,
         condition_id: 1,
         blurb: "The Wheel of Time turns and Ages come and pass. What was, what will be, and what is, may yet fall under the Shadow.",
         created_at: "2021-12-15 19:59:03",
         updated_at: "2021-12-15 19:59:03",
         format: App\Models\Format {#2112
           id: 5,
           format: "Pocket",
           created_at: "2021-12-15 19:59:03",
           updated_at: "2021-12-15 19:59:03",
         },
         genre: App\Models\Genre {#2113
           id: 1,
           genre: "Fantasy",
           created_at: "2021-12-15 19:59:03",
           updated_at: "2021-12-15 19:59:03",
         },
         authors: Illuminate\Database\Eloquent\Collection {#2115
           all: [
             App\Models\Author {#2117
               id: 1,
               first_name: "Robert",
               last_name: "Jordan",
               slug: "jordan-robert",
               created_at: "2022-01-16 11:08:37",
               updated_at: "2022-01-16 11:08:37",
               pivot: Illuminate\Database\Eloquent\Relations\Pivot {#2107
                 book_id: 1,
                 author_id: 1,
               },
             },
           ],
         },
       },
     ],
   }
```

As you can see you get each relationship as a collection inside the collection.

Views is something that is not natively supported by Laravel, so you need to use the `DB` facade
to create the view in your migration file.

```php
DB::statement("
CREATE OR REPLACE VIEW book_views (
    name,
    title,
    published,
    part,
    format,
    genre,
    publisher,
    series
)
AS
SELECT a.name,
       b.title,
       b.published,
       b.part,
       f.format,
       g.genre,
       p.publisher,
       s.series
FROM books b
JOIN author_books ab
ON b.id = ab.book_id
JOIN authors a
ON ab.author_id = a.id
JOIN formats f
ON b.format_id = f.id
JOIN genres g
ON b.genre_id = g.id
JOIN publishers p
ON b.publisher_id = p.id
JOIN series s
ON b.serie_id = s.id;
");
```

Don't forget to drop it in your rollback method.

```php
DB::statement("
    DROP VIEW IF EXISTS book_views;
");
```

As you might have seen, there are no `WHERE` conditions in the view created, you can use them if you
like. For example if you have a blog, and you only want to display published posts, you can create a
view called `published_posts` and then use that in your `PostsController`. That way you don't need to use a scope or a where clause in your eloquent query.

To query the view we treat it like we would any other table in the database.

```sql
SELECT * FROM book_views;
```

We can use where conditions as well.

```sql
SELECT * 
FROM book_views
WHERE title = 'The Eye Of The World';
```

We can use the query builder like one any other table, however for Eloquent we need to create a
model for the view, just like we would a regular table.

**Views are read only**, it is very important to remember that you need to handle any inserts/updates/deletes on the individual tables.

The biggest benefits in my opinion is that you can keep the Eloquent "eloquent" and your can easily sort
on any columns in your view. Sorting on a foreign key value is always a bit tricky in the query builder and Eloquent, however with a view it's as easy as doing it on a single table.

Take our `book_views` as an example, we want to sort on the following columns.

* Author name
* series
* part
* published

To do this without a view with Eloquent gets quite messy, since we need to sort on one many-to-many relation and two one-to-many, but with a view it would look like this.

```php
BookView::orderBy('name')
    ->orderBy('series')
    ->orderBy('part')
    ->orderBy('published')
    ->get()
```

If you make a Google search you will find that quite a few people do the sorting with php instead of
letting the database handle the sorting, that is a bad practice. The database is always faster on sorting than php will
ever be. So as long as it's possible let the database to the lifting for you.

## Materialized views

Materialized views are similar to regular views with one main difference, a view is a reference to one or more tables
while a materialized view is a copy of one or more tables. That means that you copy the records from the sources table(s)
to the materialized view. This is a good choice if you have a very large table, and you only want to use a small portion of it.
Depending on how often the data in the source table changes, you need to set up an update interval accordingly. 
This could be from every ten minutes to once a day, or even longer if you want it to.

Sad to say, but MySQL doesn't support materialized views like Oracle does, it needs a good deal of knowledge about 
PL/SQL to pull it off. I will not cover that here, since it's a whole blog post on its own. 

This [guide](https://fromdual.com/mysql-materialized-views) covers the basics of creating materialized
views in MySQL.

And this [guide](https://coding-dude.com/wp/databases/creating-mysql-materialized-views/) covers an alternative way of refreshing the materialized view.

## Federated tables

Federated tables or rather tables in another database. Another database? Yes another database and not only on the same 
database server. In an Oracle database this is called a database link that links to the given schema, but in MySQL it
links to a given table in the remote database. This is perfect for fetching data from another database,
and you don't want to store snapshots of the remote table on your
local database server.

You need to have a MySQL plugin installed and enabled to get this to work, `ha_federated.so`.

You need to make sure that the federated engine is enabled in your MySQL installation. You can do that
by running  `show engines`

It will probably give you a list that looks something like this

* MRG_MyISAM	YES	
* CSV	YES	
* MEMORY	YES	
* MyISAM	YES	
* Aria	YES	
* InnoDB	DEFAULT	
* PERFORMANCE_SCHEMA	YES	
* SEQUENCE	YES		

If you have one saying `FEDERATED YES` then you have it out of the box, otherwise you need to set it up.

You can do this by adding the `federated` keyword to your mysql config file. Most likely `my.cnf` and most likely 
located in your `/etc` directory. You need to restart MySQL after making the change.

If you now run the `show engines` again you should hopefully see the `FEDERATED YES` in your list of engines. 
If not do a Google search on how to install and enable it on your operating system.

Let's say that everything went well, and we can move on to creating a federated table.

The syntax is very straight forward, we'll use an `authors` table as an example.

```sql
CREATE TABLE authors_federated (id NUMBER, name VARCHAR(255))
ENGINE=FEDERATED
CONNECTION='mysql://<username>:<password>@<host:port>/<database>/authors;
```

Now we can use the table as we created in our local database to access the information in the remote
table.

This is from the MySQL [docs](https://dev.mysql.com/doc/refman/8.0/en/federated-storage-engine.html).

> The FEDERATED storage engine lets you access data from a remote MySQL database without using 
> replication or cluster technology. Querying a local FEDERATED table automatically pulls the data 
> from the remote (federated) tables. No data is stored on the local tables.
>
> To include the FEDERATED storage engine if you build MySQL from source, invoke CMake with the
>  `-DWITH_FEDERATED_STORAGE_ENGINE` option.
>
> The FEDERATED storage engine is not enabled by default in the running server; to enable FEDERATED, 
> you must start the MySQL server binary using the --federated option.

## One-to-many With Different Properties

Que?

I think we need to set up the world so to speak before we get into this one.
Let say that we have an online store with all kinds of different products and these products have loads of different 
properties, and we need a good database model for this.

We start small with three products.

* Black T-shirt
* White shoes
* Laptop

Let's now list the different properties the customer might be interested in .

* Product name (They all have a name).
* Prize (They all cost something)
* Color (They most likely all have a color)
* Size (They do all have a size)
* CPU (Only valid for an electronic device, laptop in our case)
* RAM (Laptop)
* Graphics (Laptop)
* Hard-drive (Laptop)
* Screen size (Laptop)

We can go on quite a while and add more properties to our list. How are we then supposed to add an unknown number of properties to our product? We can't just add another column to the table, sooner rather than lately it will be too many and the user experience will be bad with huge forms. Another way is to use a JSON-column, but if you read my post on database design, you know it's not a good option in my opinion.

So what is a better approach then?

It is actually quite simple, we create a table with the columns.

* product_id
* property
* value

The `product_id` references the `id` column in the products table, the property is they key and the value is the value.

So in our example store we could have the following records

| product_id | property   | value     |
|------------|------------|-----------|
| 1          | color      | white     |
| 2          | color      | green     |
| 3          | color      | grey      |
| 3          | CPU        | I7-12500  |
| 3          | RAM        | 32GB      |
| 3          | Hard-drive | SSD 512GB |

Now this can of course be extracted further by changing the `property` column to a `property_id` that 
references a `properties` table, that way we minimize the risk of having several property names for the same thing.


## Storing Currency

Something as simple as storing monetary values still can give a developer a headache when doing math on those values. 
Storing the value as a `FLOAT` value is not a good idea, since it will give some strange results sooner or later since
a `FLOAT` is not an exact value. You can use `DECIMAL` which is exact for this but my suggestion is to store the amount
as the lowest unit of the currency. That way you are not locked into how many positions the amount is allowed to have. 
I will use US Dollars and European EUROS as examples.

If something costs $3.50 multiply it by 100 and store it as 350 cents and then divide it by 100 before you display it. 
The same goes for Euros, €4.50 should be stored as 450 cents.

If you follow this standard way of storing currency you will be saved from a lot of headaches when doing calculations with decimal points.

This is goes for most of the values with decimal points, if possible store them as integers.

## Laravel Tips and Tricks

If you don't use Laravel you can skip this section.
I would like to thank my friend [René Sinnbeck](https://sinnbeck.dev/) for the input to this section.

### Eager loading just what you need.

Earlier in this post I stated that an eager loaded relation would work fine as long as you didn't mind pulling all the 
columns for each eager loaded table, this is not strictly true, you can tell 
Eloquent which columns to fetch. You just add a colon `:` after the relationship followed by a comma seperated list of
columns.

```php
$books = Book::with('formats:id,format')->get();
```

### Eloquent aliases

When I talked about joins earlier I gave the tables that we joined aliases, 
but I didn't do that in the query builder version.

```sql
SELECT b.*, f.format
FROM books b
[INNER] JOIN formats f
ON b.format_id = f.id;
```

You can do that in the query builder as well.

```php
DB::table('books as b')
    ->join('formats as f', 'b.format_id', '=', 'f.id')
    ->select('b.*', 'f.format')
    ->get();
```

Aliases are optional, but they can be very useful and sometimes necessary if you need to join the same table 
more than once.     

## Conclusion

I hope this post has shed some more light on how to use the database in a better and more efficient way. 

It is by design that I have not covered on how to use any of these numeric data types that are available
in the database. 

* DECIMAL
* FLOAT
* DOUBLE

If you want you can read up on those formats here

[DECIMAL](https://dev.mysql.com/doc/refman/8.0/en/fixed-point-types.html)

[FLOAT/DOUBLE](https://dev.mysql.com/doc/refman/8.0/en/floating-point-types.html)


//Tray2









