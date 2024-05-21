---
title: Properly formed foreign keys are your best friends
slug: properly-formed-foreign-keys-are-your-best-friends
created_at: 2022-09-14
updated_at: 2022-09-14
published_at: 2022-09-14
author: Tray2
summary: A few days ago a user at the Laracasts forum had performance issue with one of his queries that used joins. While a join can cause some performance slow-downs, it shouldn't be anywhere near the 12 seconds he claimed that the query took. This post here will explain what the issue probably was and how to speed that up.
---

A few days ago a user at the Laracasts forum had performance issue with one of his queries that used joins.
While a join can cause some performance slow-downs, it shouldn't be anywhere near the 12 seconds he claimed 
that the query took. This post here will explain what the issue probably was and how to speed that up. I will be using
two tables, `authors` and `books` in my example. There is a classic one-to-many relationship between them.

I've created a fresh Laravel project for this, configured the database, and have created the following 
using the artisan command.

* Author model
* Author factory
* Authors migration
* Book model
* Book factory
* Books migration

So let's start with setting up the migrations for our two tables.

Our authors table will just contain the name of the author.

```php
public function up()
{
    Schema::create('authors', function (Blueprint $table) {
        $table->id();
        $table->string('author');
        $table->timestamps();
    });
}
```

And for simplicity’s sake we keep the columns in the books table to a bare minimum as well.

```php
public function up()
{
    Schema::create('books', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('author_id');
        $table->string('title');
        $table->integer('published');
        $table->timestamps();
    });
}
```

Now let's update the factories that we created as well, so that we can easily generate fake data.

In the `AuthorFactory` we add a faker for the name.

```php
public function definition()
{
    return [
        'name' => $this->faker->name(),
    ];
}
```

And for the `BookFactory` we add fakers for the title and published columns. We also call the `AuthorFactory`
to create the author for the book.

```php
public function definition()
{
    return [
        'title' => $this->faker->words(3, true),
        'published' => $this->faker->year(),
        'author_id' => Author::factory()->create()->id,
    ];
}
```

Now that we defined the migrations, model and factories let's migrate our database, and fake up some data to
play around with.

```shell
php artisan migrate
```

We should see something similar to this.

```shell
 INFO  Preparing database.

  Creating migration table ......................................... 86ms DONE

   INFO  Running migrations.

  2014_10_12_000000_create_users_table ............................ 155ms DONE
  2014_10_12_100000_create_password_resets_table ................... 88ms DONE
  2019_08_19_000000_create_failed_jobs_table ...................... 125ms DONE
  2019_12_14_000001_create_personal_access_tokens_table ........... 172ms DONE
  2022_09_13_032339_create_authors_table ........................... 26ms DONE
  2022_09_13_032349_create_books_table ............................. 29ms DONE
```

Now you can use `php artisan tinker` to run the following code to create the fake data, but I prefer
using [Tinkerwell](https://tinkerwell.app/).

```php
Author::factory()->count(1000)->create()->each(function ($author){
  Book::factory()->count(10)->create([
    'author_id' => $author->id,
  ]);
});
```

Now we should have 1000 authors and with 10 books each, totaling 10000 books.

```shell
MariaDB [foreign_keys]> select count(*) from authors;
+----------+
| count(*) |
+----------+
|     1000 |
+----------+
1 row in set (0,000 sec)

MariaDB [foreign_keys]> select count(*) from books;
+----------+
| count(*) |
+----------+
|    10000 |
+----------+
1 row in set (0,000 sec)
```

So let's start querying the database.

We will start with a basic join and select all the records.

```sql
SELECT authors.name, books.title
FROM books
JOIN authors 
ON books.author_id = authors.id;
```

This will give us a list of 10000 books and the author connected to the book.

```shell
| Kade Huel                     | autem et pariatur                      |
| Kade Huel                     | aperiam aliquid nulla                  |
| Kade Huel                     | et cupiditate sint                     |
| Kade Huel                     | saepe quia at                          |
| Kade Huel                     | ea dolorum accusantium                 |
| Kade Huel                     | ad velit quia                          |
| Kade Huel                     | vitae laborum iusto                    |
| Kade Huel                     | ea vero cum                            |
| Kade Huel                     | consequatur vel porro                  |
| Kade Huel                     | vel assumenda dicta                    |
+-------------------------------+----------------------------------------+
10000 rows in set (0,005 sec)
```

That is pretty fast, so let's take a look at the execution plan. To do that we put the word `EXPLAIN` in
front of the query like so.

```sql
EXPLAIN SELECT authors.name, books.title
FROM books
JOIN authors 
ON books.author_id = authors.id;
```

```shell
MariaDB [foreign_keys]> EXPLAIN SELECT authors.name, books.title FROM books JOIN authors  ON books.author_id = authors.id;
+------+-------------+---------+--------+---------------+---------+---------+------------------------------+-------+-------+
| id   | select_type | table   | type   | possible_keys | key     | key_len | ref                          | rows  | Extra |
+------+-------------+---------+--------+---------------+---------+---------+------------------------------+-------+-------+
|    1 | SIMPLE      | books   | ALL    | NULL          | NULL    | NULL    | NULL                         | 10000 |       |
|    1 | SIMPLE      | authors | eq_ref | PRIMARY       | PRIMARY | 8       | foreign_keys.books.author_id | 1     |       |
+------+-------------+---------+--------+---------------+---------+---------+------------------------------+-------+-------+
2 rows in set (0,000 sec)
```

Since we didn't use any conditions other than that the books `author_id` should equal the `id` in the authors
table.

So let's up the ante a bit and tell it that we want only the books with the `author_id` of 10.

```sql
SELECT authors.name, books.title
FROM authors
JOIN books
ON books.author_id = authors.id
WHERE author_id = 10;
```

The result would look something like this.

```shell
+-----------------+------------------------+
| name            | title                  |
+-----------------+------------------------+
| Blaise Turcotte | maxime nisi ut         |
| Blaise Turcotte | qui dolores est        |
| Blaise Turcotte | assumenda optio velit  |
| Blaise Turcotte | dolor nam voluptatem   |
| Blaise Turcotte | voluptas maiores qui   |
| Blaise Turcotte | omnis cumque molestias |
| Blaise Turcotte | nisi id quisquam       |
| Blaise Turcotte | eum temporibus maiores |
| Blaise Turcotte | dolor eum quae         |
| Blaise Turcotte | dolore facere quo      |
+-----------------+------------------------+
10 rows in set (0,002 sec)
```

It is still pretty fast as you can see, but if we run an explain on the query we will see something
nasty.

```shell
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
| id   | select_type | table   | type  | possible_keys | key     | key_len | ref   | rows  | Extra       |
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
|    1 | SIMPLE      | authors | const | PRIMARY       | PRIMARY | 8       | const | 1     |             |
|    1 | SIMPLE      | books   | ALL   | NULL          | NULL    | NULL    | NULL  | 10000 | Using where |
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
2 rows in set (0,000 sec)
```

Take a look at the `rows` on the second line in the table, we are looking at 10000 records to find the
books that belongs to the author with the `id` of 10, but what if we change the query and query for the 
`id` of the authors table instead of the `author_id` on the books table? 

Well let's find out.

```sql
SELECT authors.name, books.title
FROM authors
JOIN books
ON books.author_id = authors.id
WHERE authors.id = 10;
```

Well the time seems to be the same.

```shell
+-----------------+------------------------+
| name            | title                  |
+-----------------+------------------------+
| Blaise Turcotte | maxime nisi ut         |
| Blaise Turcotte | qui dolores est        |
| Blaise Turcotte | assumenda optio velit  |
| Blaise Turcotte | dolor nam voluptatem   |
| Blaise Turcotte | voluptas maiores qui   |
| Blaise Turcotte | omnis cumque molestias |
| Blaise Turcotte | nisi id quisquam       |
| Blaise Turcotte | eum temporibus maiores |
| Blaise Turcotte | dolor eum quae         |
| Blaise Turcotte | dolore facere quo      |
+-----------------+------------------------+
10 rows in set (0,002 sec)
```

The execution plan as well.

```shell
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
| id   | select_type | table   | type  | possible_keys | key     | key_len | ref   | rows  | Extra       |
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
|    1 | SIMPLE      | authors | const | PRIMARY       | PRIMARY | 8       | const | 1     |             |
|    1 | SIMPLE      | books   | ALL   | NULL          | NULL    | NULL    | NULL  | 10000 | Using where |
+------+-------------+---------+-------+---------------+---------+---------+-------+-------+-------------+
2 rows in set (0,000 sec)
```

We are still looking at 10000 rows, but what if we add an index on the `author_id` column in the books
table, sure we can do that, but there is a better way to handle this, and that is called a foreign key
constraint, it is an index but with some added benefits, we can make sure that we can't create a book with
an `author_id` that does not exist in the authors table, and we can utilize something called the `cascade`,
which basically is a way to control what happens to a record when a certain action is done on the table that
the foreign key references. For example, we can delete all the books belonging to an author when the author
is deleted, but we can talk more about that in another post.

So what do we need to do to create a foreign key constraint, well all we need to do is update our migration
for the books table, migrate the database, and reseed it with fake data.

So in the books table migration we change the `author_id` column like so.

```php
//$table->unsignedBigInteger('author_id');
  $table->foreignId('author_id')->constrained()
```

Then we run this command to reseed the database.

`php artisan migrate:fresh`

After that we can once again seed the database with fake data.

```php
Author::factory()->count(1000)->create()->each(function ($author){
  Book::factory()->count(10)->create([
    'author_id' => $author->id,
  ]);
});
```

Okay, now let's run the query again, remember that the last run took 0,002 sec.

```sql
SELECT authors.name, books.title
FROM authors
JOIN books
ON books.author_id = authors.id
WHERE authors.id = 10;
```

```shell
+------------------+-------------------------------+
| name             | title                         |
+------------------+-------------------------------+
| Dr. Neva Kilback | velit eum est                 |
| Dr. Neva Kilback | eligendi soluta aut           |
| Dr. Neva Kilback | a velit aut                   |
| Dr. Neva Kilback | soluta distinctio dolorem     |
| Dr. Neva Kilback | est aut assumenda             |
| Dr. Neva Kilback | occaecati vero iste           |
| Dr. Neva Kilback | ratione rerum qui             |
| Dr. Neva Kilback | voluptates reiciendis error   |
| Dr. Neva Kilback | laborum laudantium aut        |
| Dr. Neva Kilback | voluptate dignissimos dolores |
+------------------+-------------------------------+
10 rows in set (0,000 sec)
```

Wow, now it took so little time it didn't even register, so let's look at the plan.

```shell
+------+-------------+---------+-------+-------------------------+-------------------------+---------+-------+------+-------+
| id   | select_type | table   | type  | possible_keys           | key                     | key_len | ref   | rows | Extra |
+------+-------------+---------+-------+-------------------------+-------------------------+---------+-------+------+-------+
|    1 | SIMPLE      | authors | const | PRIMARY                 | PRIMARY                 | 8       | const | 1    |       |
|    1 | SIMPLE      | books   | ref   | books_author_id_foreign | books_author_id_foreign | 8       | const | 10   |       |
+------+-------------+---------+-------+-------------------------+-------------------------+---------+-------+------+-------+
2 rows in set (0,000 sec)
```

If we look at the `rows` column we see that instead of looking at 10000 rows we only look at 10 rows, and if we
look as the `type` column, it is now `ref` instead of `all` as it was previously. We can also see that the query
now both has the possibility to use and is using the `books_author_id_foreign` index. This is exactly what we want.

So we gained about 0.0002 seconds in the execution, big deal right? 

Yes, it's a huge deal, let me prove it to you.

First we change the migration back to the way we wrote it the first time.

```php
$table->unsignedBigInteger('author_id');
//$table->foreignId('author_id')->constrained();
```

We then migrate fresh to make the changes in the database.

`php artisan migrate:fresh`

We then make a small change to our fake data producing code, so that we create 10000 authors with 100 books each.
This will take some time to generate, we are talking about one million records here so be patient.

```php
Author::factory()->count(10000)->create()->each(function ($author){
  Book::factory()->count(100)->create([
    'author_id' => $author->id,
  ]);
});
```

Now we can run the query again.

```sql
SELECT authors.name, books.title
FROM authors
JOIN books
ON books.author_id = authors.id
WHERE author_id = 10;
```

The time has increased by a lot as you can see.

```shell
| Zachary Leannon | qui sint odit                      |
| Zachary Leannon | qui enim quia                      |
| Zachary Leannon | beatae reiciendis culpa            |
| Zachary Leannon | ut dolorum repudiandae             |
| Zachary Leannon | excepturi dolor explicabo          |
| Zachary Leannon | et sunt optio                      |
+-----------------+------------------------------------+
100 rows in set (0,150 sec)
```

From 0,002 to 0,150, now that is quite a lot, but understandable since it needs to go through a million records.
If you don't believe me we can check the execution plan.

```shell
+------+-------------+---------+-------+---------------+---------+---------+-------+---------+-------------+
| id   | select_type | table   | type  | possible_keys | key     | key_len | ref   | rows    | Extra       |
+------+-------------+---------+-------+---------------+---------+---------+-------+---------+-------------+
|    1 | SIMPLE      | authors | const | PRIMARY       | PRIMARY | 8       | const | 1       |             |
|    1 | SIMPLE      | books   | ALL   | NULL          | NULL    | NULL    | NULL  | 1000000 | Using where |
+------+-------------+---------+-------+---------------+---------+---------+-------+---------+-------------+
2 rows in set (0,000 sec)
```

Now we need to change it back again so that we create the foreign key, but since it took so long time to
seed the database, we can try to add the foreign key constraint directly in the database, so we don't have
to reseed it.

```sql
ALTER TABLE books
    ADD FOREIGN KEY
    (author_id)
    REFERENCES authors (id);
```

That took about 5 seconds to complete, while doing reseed would have taken several minutes.

Now let's run our query again and see what happens.

```sql
SELECT authors.name, books.title
FROM authors
JOIN books
ON books.author_id = authors.id
WHERE author_id = 10;
```

```shell
| Zachary Leannon | quia error ipsum                   |
| Zachary Leannon | asperiores minima ipsam            |
| Zachary Leannon | qui sint odit                      |
| Zachary Leannon | qui enim quia                      |
| Zachary Leannon | beatae reiciendis culpa            |
| Zachary Leannon | ut dolorum repudiandae             |
| Zachary Leannon | excepturi dolor explicabo          |
| Zachary Leannon | et sunt optio                      |
+-----------------+------------------------------------+
100 rows in set (0,026 sec)
```

It took 0,026 seconds instead of the 0,150 it did before. So I would say that is a good improvement.

So remember when to always create your foreign keys properly with a foreign key index, it will speed up your 
application. If you do that your joins will be faster and those 12 seconds I mentioned in the beginning of this
post will be nothing but a memory.

There are of course things we need to consider.
The data is never inserted the way we did it, we inserted the book 100 at the time, and they all had the same
author id, so the database was pretty much ordered from the beginning. Now we did this with one million records,
what if you have 50 million records, well the foreign key index will help you decrease the execution time, there 
are however limits to what an index can do, and if that limit is reached you can use something called partitions,
which is way beyond the scope of this post.

//Tray2
