---
title: Use a view instead of a complex Eloquent query in your Laravel application
slug: use-a-view-instead-of-a-complex-eloquent-query-in-your-laravel-application
created_at: 2022-10-01
updated_at: 2022-10-01
published_at: 2022-10-01
author: Tray2
summary: What is a database view, and how can we use it to make our code cleaner?.
---
What is a database view, and how can we use it to make our code cleaner?
This is not to be mixed up with the more common view file in Laravel, or any MVC framework for that matter.
A database view or more commonly known as a `view`, is a way to push a more complex query
into the database and then make a simpler query against it, kinda like a sub-query, but 
inside the database. You could also see it as a readonly table, or as a table full of
generated columns. I have talked about views in several of my earlier posts, but I think
it's time to put that into practice. I would love to show you a really complex query, and
the difference between the Eloquent query against several tables, and how it would look
when using a view in the database, but I haven't managed to get that query to work in Eloquent.

We will create another simpler, but still very real life query, and improve it by using a view
instead of a more complex Eloquent query. So with not much further a do we start.

We have these tables that we need to join, and create queries for.

* Artists
* Records
* Genres
* Formats

I believe that you already know how to create migrations for your database, so I will just show
the table structures for the tables mentioned above. Some tables are a bit slimmed down
since the full version won't add any more meaning for this post. 

The `artists` table.

```shell
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| name       | varchar(255)        | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
```

The `records` table.

```shell
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| title      | varchar(255)        | NO   |     | NULL    |                |
| released   | int(11)             | NO   |     | NULL    |                |
| artist_id  | bigint(20) unsigned | NO   |     | NULL    |                |
| genre_id   | bigint(20) unsigned | NO   |     | NULL    |                |
| format_id  | bigint(20) unsigned | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
```

The `genres` table.

```shell
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| name       | varchar(255)        | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
```

Lastly the `formats` table.

```shell
+------------+---------------------+------+-----+---------+----------------+
| Field      | Type                | Null | Key | Default | Extra          |
+------------+---------------------+------+-----+---------+----------------+
| id         | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| name       | varchar(255)        | NO   |     | NULL    |                |
| created_at | timestamp           | YES  |     | NULL    |                |
| updated_at | timestamp           | YES  |     | NULL    |                |
+------------+---------------------+------+-----+---------+----------------+
```

Now that we have introduced the four players, it's time to put some use-cases into play.

* The records should be ordered by artist in the index view.
* Records by the same artist should be ordered by the year they were released.

To make that happen in our controller we need would normally have to do something like this.

```php
Record::query()
  ->join('artists','records.artist_id', '=', 'artists.id')
  ->join('genres', 'records.genre_id', '=', 'genres.id')
  ->join('formats', 'records.format_id', '=', 'formats.id')
  ->select('records.*', 
           'artists.name AS artist',
           'genres.name AS genre', 
           'formats.name AS format')
  ->orderBy('artists.name')
  ->orderBy('records.released')
  ->get();
```

It's not hideous, but we can make it so much prettier with by using a view. Now Laravel doesn't have any nice way
of creating views like it does with creating tables, but we can still use a migration to create the view.

So we create a new migration, and I like to follow the Laravel naming convention for tables even for my views,
it makes them a bit odd-ish, but we get the normal support as we would for a table.

```php
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW record_index_views AS
            SELECT `records`.*, 
                   `artists`.`name` AS `artist`,
                   `genres`.`name` AS `genre`, 
                   `formats`.`name` AS `format` 
            FROM `records`
            INNER JOIN `artists`
              ON `records`.`artist_id` = `artists`.`id`
           INNER JOIN `genres` ON `records`.`genre_id` = `genres`.`id`
           INNER JOIN `formats` ON `records`.`format_id` = `formats`.`id`;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS record_index_views;');
    }
```

Nex step is to create a model for our view so that we can use Eloquent on it, and it should be named `RecordIndexView`,
that way it by default knows the name of our view. Also notice that I didn't use any `ORDER BY`s in the view, 
any ordering should be handled in the query.

So let's update the query in our controller.

```php
RecordIndexView::query()
  ->orderBy('artist')
  ->orderBy('released')
  ->get();
```

As you can see way cleaner, and if you want to filter it, paginate it, or whatever else you would to with your table,
go ahead. The only things you can't do is, update and delete records in the view since it read-only.

Notice also that we told the view exactly which columns to pull out of each table, and as you know, you shouldn't
pull more columns than you will need, and even if you include them in the view, you can still tell Eloquent which
columns it should get.

So when can you use a view instead of a table?
You can use it for all the read queries, that means that you can have an index view like I've shown you, but you
could also have one for show and even edit (as long as you don't use it in your store, update or delete queries).
So any time you want to pull data from the database, and it includes more than one table, using a view is an option.
So if the route method is a `GET` you could use a view. The view is completely dynamic so no data is stored in it.
That means that you don't need to worry about storing information in more than one place, the base tables.

Another example would be if you have a blog, and you have three kinds of posts.

* Draft
* Unpublished
* Published

Then a view might be an option to using a query scope.

```php
    //View
    PostDraftView::all();
    //Scope
    Post::draft()->get();
    
    //View
    PostUnpublishedView::all();
    //Scope
    Post::unpublished()->get();
    
    //View
    PostPublishedView::all();
    //Scope
    Post::published()->get();
```

I hope this post has been an interesting read, and that you have learned how to use a view instead of a complex 
query in you controller, or where ever you choose to place the logic. Sometimes there is no need for a view, but
on occasion there is. 

//Tray2
