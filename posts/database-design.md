---
title: The value of a good database model
slug: database-design
created_at: 2021-11-13
updated_at: 2021-11-13
published_at: 2021-11-13
author: Tray2
summary: You might think that the way your store your data isn't really that important. Well it's more important than you think it is. A good database design just like good clean code is the key to performance, not only for the end user but also for you the developer. A poor database model just like poorly written code will slow you down and furthermore it will slow your database queries down which results in a slow application. Just like with the SOLID principles there are some things to consider when developing a database model.
---

You might think that the way your store your data isn't really that important.
Well it's more important than you think it is.

A good database design just like good clean code is the key to performance, not
only for the end user but also for you the developer. 
A poor database model just like poorly written code will slow you down and furthermore it 
will slow your database queries down which results in a slow application.

Just like with the `SOLID` principles there are some things to consider when developing a database model.
I will try to explain this with a hands-on example.

We have been tasked to create a small application that a user can store information about 
their music (record) collection. So we need to store the following information.

* Artist/Group
* Title
* Release year
* Format
* Genre
* Number of tracks
* Condition
* Track number
* Track titles
* Track mixes
* Track lengths
* Track artists

There are many ways to accomplish this and some of them are better than others and some of them are plain terrible.

Let's start with an approach that I have seen way too many times, the single Json column approach.

## Single json column approach

You create your table with a Json column. 
In MariaDB the json column has the type longtext

```sql
CREATE TABLE records( 
  id int auto_increment primary key, 
  record json,
  created_at timestamp,
  updated_at timestamp 
);
```

If you use Laravel you would have a migration that looks something like this.

```php
Schema::create('records', function (Blueprint $table) {
    $table->id();
    $table->json('records');
    $table->timestamps();
}
```


Then you create your json object

```javascript
 {
     "artist": "Iron Maiden",
     "title": "Killers",
     "released": 1981,
     "format": "LP",
     "genre": "Heavy Metal",
     "no_of_tracks": 10,
     "condition": "Mint",
     "tracks": [
         {
            "track_number": 1,
            "track_title": "The Ides Of March",
            "track_mix": "Album",
            "track_length": "1:48",
            "track_artist": "Iron Maiden"
         },
         {
            "track_number": 2,
            "track_title": "Wrathchild",
            "track_mix": "Album",
            "track_length": "2:54",
            "track_artist": "Iron Maiden"
         },
         {
            "track_number": 3,
            "track_title": "Murders In The Rue Morue",
            "track_mix": "Album",
            "track_length": "4:14",
            "track_artist": "Iron Maiden"
         }
         //...the rest of the tracks here
     ]
      
 }
```

The good thing about this approach is that you can store everything in a single table but that is about it.
The bad thing about this approach is that it gets trickier to search for the title of the record 
and even more tricky to search for a specific track title on the record. 
Not only will your queries be more complex, they will also be slower since you can't really index a json column.
There are some ways to do it, but it would be way easier to use a regular table(s) for storing the information.

So why shouldn't you use a single json column

* It's hard to do CRUD on a record or track
* Can't really use indexes
* It's not dry (don't repeat yourself)
* You get more data than you need

### Conclusion:

Don't use this approach since it defeats the purpose of a relational database and there are better
ways to do this as I soon will show you.

## The hybrid table with a json column

In this one you mix the use of regular columns with one json column.
Like I said earlier, in MariaDB the Json column type is called longtext.

### A brief note on naming conventions.

What a table is named might not seem that important, but it is. Specially when using a framework like Laravel.
There aren't that many rules and they are quite easy to follow.

A table is named by its contents plural form. So if the table stores information about a car 
the name of the table becomes cars. You get the gist, and I will be using that naming convention
throughout this post.

The only time this will differ is when creating a pivot table then it should be named from the two joined tables
in singular form and in alphabetical order. So if you join a record to an artist with a pivot the name would become
artist_record.

If for some reason your table contains house colors then you should use snake case in the name.
So the table name would be house_colors.

The column names should be in singular form and if they contain multiple words the should just like the 
table use snake case. Registration number would become registration_number. 

All foreign keys should have the table they are referencing in singular followed by an id. 
So if a post has a user connected to it the column name would become user_id.

There are a some architects and developers that likes to prefix their column names with the table name.
In a records' table the title would be prefixed with records thus becoming records_title.
Please don't use that naming convention and if you are going to use that convention there better be a damn
good reason for it.

Don't use camel case in your table or column names.

```sql
CREATE TABLE records(
     id int auto_increment primary key,
     artist varchar(255),
     title varchar(255),
     released int,
     format varchar(255),
     genre varchar(255),
     no_of_tracks int,
     condition varchar(255),
     tracks json,
     created_at timestamp,
     updated_at timestamp
);
```

In a laravel migration it would look something like this.

```php
Schema::create('records', function (Blueprint $table) {
    $table->id();
    $table->string('artist');
    $table->string('title');
    $table->int('released');
    $table->string('format');
    $table->string('genre');
    $table->int('no_of_tracks');
    $table->string('condition');
    $table->json('tracks');
    $table->timestamps();
}
```

If you then would do a select you would get a result similar to this.

```
---------------------------------------------------------------------------------------------------------------------+
|id|artist    |title  |released|format|genre      |no_of_tracks|condition|tracks                                     |
+-+-----------+-------+--------+------+-----------+------------+---------+-------------------------------------------+
|1|Iron Maiden|Killers|1981    |LP    |Heavy Metal|10          |Mint     |[{"track_number": 1,                       |   
                                                                         |  "track_title": "The Ides Of March",      |
                                                                         |  "track_mix": "Album",                    |
                                                                         |  "track_length": "1:48",                  |
                                                                         |  "track_artist": "Iron Maiden"},          |  
                                                                         | {"track_number": 2,                       |   
                                                                         | "track_title": "Wrathchild",              |
                                                                         | "track_mix": "Album",                     |
                                                                         | "track_length": "2:54",                   |
                                                                         | "track_artist": "Iron Maiden"},           |
                                                                         | {"track_number": 3,                       |
                                                                         | "track_title": "Murders In The Rue Morue",|
                                                                         | "track_mix": "Album",                     |
                                                                         | "track_length": "4:14",                   | 
                                                                         | "track_artist": "Iron Maiden"}]           |
+-+------+----+-+-----+---+----+------+------+----+------------+---------+-------------------------------------------+
1 row in set (0.000 sec)
```

Using this approach still put everything in the same table and while it's better than the 
first approach because you can easily index and do CRUD operations on the record you still 
have the same issue with the tracks. 

So why shouldn't you use the hybrid approach

* It's hard to do CRUD on a track
* Can't really use indexes on the tracks
* It's DRYer but not DRY
* You get more data than you need

### Conclusion:

Don't use this approach.

## The two table approach

With this approach we extract the tracks to its own table and use a foreign key to create a
relation between the two tables.

### The records table

```sql
CREATE TABLE records( 
  id int auto_increment primary key,
  artist varchar(255),
  title varchar(255),
  released int,
  format varchar(255),
  genre varchar(255),
  no_of_tracks int,
  condition varchar(255),
  created_at timestamp,
  updated_at timestamp
);
```

Laravel Migration 

```php
Schema::create('records', function (Blueprint $table) {
    $table->id();
    $table->string('artist');
    $table->string('title');
    $table->int('released');
    $table->string('format');
    $table->string('genre');
    $table->int('no_of_tracks');
    $table->string('condition');
    $table->timestamps();
}
```

### The tracks table

```sql
CREATE TABLE tracks( 
  id int auto_increment primary key,
  track_no int,
  title varchar(255),
  mix varchar(255),
  length varchar(255),
  track_artist varchar(255),
  record_id int,
  created_at timestamp,
  updated_at timestamp 
);
```

Laravel Migration

```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->int('track_no');
    $table->string('title');
    $table->string('mix');
    $table->string('length');
    $table->string('track_artist');
    $table->foreign('record_id');
    $table->timestamps();
}
```

Our database model now looks like this

<img class="w-full" src="/assets/images/two_table_no_relation.png" alt="Two tables no relation">

We can then tell our database that there is a relation between the id of our records table and our tracks table.
While doing that we also prevent the creation of tracks that does not belong to a record.

```sql
CREATE TABLE tracks( 
  id int auto_increment primary key,
  track_no int(255),
  title varchar(255),
  mix varchar(255),
  length varchar(255),
  track_artist varchar(255),
  record_id int,
  created_at timestamp,
  updated_at timestamp, 
  FOREIGN KEY (record_id) REFERENCES records(id)
);
```

Laravel Migrationfile

```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->int('track_no');
    $table->string('title');
    $table->string('mix');
    $table->string('length');
    $table->string('track_artist');
    $table->foreignId('record_id')
        ->constrained('records');
    $table->timestamps();
}
```

This is what our model looks like with the relation defined.

<img class="w-full" src="/assets/images/two_table_with_relation.png" alt="Two tables with relation">

The important thing here is that the data type of both the id and the record_id are exactly the same,
or you will get an error when creating the tracks table. 
You must also create the records table first otherwise you will get an error while creating the tracks table.
Another benefit to linking the tables with the `id` and the `record_id` is that you can use cascade
to make changes in the related table.
For example if you delete the record you will also delete all the track that belongs to that record.
To make that work we need to tell the database that it should cascade the action. 

This is how you can do that

```sql
CREATE TABLE tracks( 
  id int auto_increment primary key,
  track_no int,
  title varchar(255),
  mix varchar(255),
  length varchar(255),
  track_artist varchar(255),
  record_id int,
  created_at timestamp,
  updated_at timestamp, 
  FOREIGN KEY (record_id) REFERENCES records(id)
    ON DELETE CASCADE
);
```

Laravel Migration


```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->int('track_no');
    $table->string('title');
    $table->string('mix');
    $table->string('length');
    $table->string('track_artist');
    $table->foreignId('record_id')
      ->constrained('records')
      ->onDelete('cascade');
    $table->timestamps();
}
```

Now we can easily use CRUD on both our tables, and we can add any necessary indexes to increase the performance.
We are also using a one to many relation between the record and the tracks. 

> A record has many tracks.

So why shouldn't you use the two table approach

* It's DRYer but not DRY

### Conclusion:

While this approach is better than the two previous ones it still lacks the DRY:ness that we need.
It still defeats the relational database purpose.
Let me explain.

We store the full artist name in our records table and most artists has more than one record.
We also store the full artists name in our tracks table. 
Then we do the same with format, genre and condition in our records table. 
While this might not seem like a big issue, it kinda is. 
Your database will be a bit bigger since you are storing more information than necessary,
and you are violating the single source of truth principle. 
Imagine you want a list of all the Iron Maiden records and someone has spelled it Iron Maden
then you would not find that record. So try not to use this approach since it can give
some unexpected results to your queries.


## The extraction principle

For every column in a table ask yourself these questions

* Can `x` have more than one `y`?
* Can `y` have more than one `x`?

So let's apply it to our original columns list.

* Artist/Group
* Title
* Release year
* Format
* Genre
* Number of tracks
* Condition
* Track number
* Track titles
* Track mixes
* Track lengths
* Track artists

### Artist

> Can an artist have more than one record?
>
> Yes!

> Can a record have more than one artist?
>
> Yes!

Now there are two approaches to the artist/record dilemma.

1. Use a many-to-many relation with a pivot table
2. Use the track artist approach that we have been doing.

I would go with the second approach since I then can give the record a `Various Artists` artist or if it's a certain
series of records featuring various artists, and you want to keep them together like the `Thunderdome` series. 
There are some edge cases, but they are so few that I don't think it warrants a many-to-many relation.

To create our artists' table we can use this SQL.

```sql
CREATE TABLE artists(
   id int auto_increment primary key,
   artist varchar(255),
   created_at timestamp,
   updated_at timestamp
);
```

If you are a Laravel user your migration would look something like this. 

```php
Schema::create('artists', function (Blueprint $table) {
    $table->id();
    $table->string('artist');
    $table->timestamps();
}
```

So we end up with an artists' table that looks like this.

<img class="w-full" src="/assets/images/artists_table.png" alt="Artists table">

### Title

> Can a record have more than one title?
>
>  Generally no. Some might have like Metallica's The Black Album aka Metallica. 
> However I think we are safe to presume a no here.

> Can a title belong to more than one record?
>
> Yes, however not likely with the same artist unless of course you count Edguy's 
> re-recording of their 1995 release Savage Poetry in 2000.

Here I would stick with keeping the title in the records' table since there is no real gain
in extracting it to its own table.

Our records' table so far.

<img class="w-full" src="/assets/images/records_table_step_one.png" alt="Records table with just title">

### Release

> Can a record have more than one release year?
>
> Yes it can, but now we are talking about reissues, and it would be better to add  
> another column to our records' table to handle that.

> Can a year have more than one release?
>
> Of course, it can but would it warrant another table? I don't think so and keeping 
> it in the records table and putting an index on it would make listing records 
> form a certain year lightening fast.

So here I would stick with keeping it in the records table.

Like so.

<img class="w-full" src="/assets/images/records_table_step_two.png" alt="Records table with title and released">

### Format

> Can a record have more than one format?
>
> Yes!

> Can a format have more than one record?
>
> Yes!

Here we have a similar situation as with the Record/Artist relation.
We could use a many to many here. However, it depends a bit on how you want to display the records. 

Do you want it like this?
* Iron Maiden - Killers LP, CD, TAPE

Or Like this?
* Iron Maiden - Killers LP
* Iron Maiden - Killers CD 
* Iron Maiden - Killers TAPE

The first option would require a many-to-many relation with a pivot table to join the formats to the record. 
The second option would just require a formats table and a foreign key in your records' table referencing
the formats table.

I would use a one to many on this by adding the foreign key to my records' table but the other approach
is fine as well.

<img class="w-full" src="/assets/images/records_table_step_three.png" alt="Records table with title and released">

### Genre

> Can a record have more than one genre?
>
> Yes!

> Can a genre have more than one record?
>
> Yes!

This one is almost the same as the format, however we can't look at how we want to display the record
to decide which approach to take.

This would make sense
* Iron Maiden - Killers Heavy Metal, Hard Rock

This would not
* Iron Maiden - Killers Heavy Metal
* Iron Maiden - Killers Hard Rock 

So the question here is, should we allow only one genre per record or multiple genres.

I would be happy to keep the genres to a minimum and use a foreign key in the records table,
but it depends on what your need is.

<img class="w-full" src="/assets/images/records_table_step_four.png" alt="Records table with title, released, format_id and genre_id">

### No_of_tracks

> Can a record have more than one number of tracks?
>
> Yes, but then it's another version of that record.

> Can a track number have many records?
>
> Yes!

This one is similar to the released column. I think using a pivot because a few percent of the records 
has the same number of tracks is overengineering it a bit. So I see no reason to extract it to a reference table.
So I would keep it in the records table.

<img class="w-full" src="/assets/images/records_table_step_five.png" alt="Records table with title, released, format_id, genre_id and no_of_tracks ">

### Condition

> Can a record have more than one condition
> 
> Yes, if you count the covers condition.

> Can a condition have more than one record?
>
> Yes!

Here you can choose to do a polymorphic relation with a pivot table with three columns

* record_id
* condition_id
* type

Where type is the cover or the record.

Or you can just add another column for the cover condition.

I would go with the added foreign key in the records table approach for this, 
but it's up to you which way you choose.

So we end up with a records that looks like this.

<img class="w-full" src="/assets/images/records_table_step_six.png" alt="The almost complete records table">

We also need to create the foreign key that connects the artists table with the records table. 
The most important thing here is that they both have the same datatype or our database will squawk at us.

<img class="w-full" src="/assets/images/records_table_step_seven.png" alt="The complete records table">

We can also add a constraint to our foreign key and with the help of that we can delete all the records 
automatically when the artist is deleted. So we create a foreign key constraint with a cascade on delete.
This constraint also prevents us from creating records that doesn't have an artist.

To create the records table we can use an SQL script that looks something like this.

```sql
CREATE TABLE records( 
  id int auto_increment primary key,
  artist_id int,
  title varchar(255),
  released int,
  format_id int,
  genre_id int,
  no_of_tracks int,
  condition_id int,
  FOREIGN KEY (artist_id)
      REFERENCES artists (id)
      ON DELETE CASCADE
);
```

And in Laravel the migration would look something like this.

```php
Schema::create('records', function (Blueprint $table) {
    $table->id();
    $table->foreign('artist_id')
        ->refrences('artists')
        ->on(id)
        ->onDelete('cascade');
    $table->string('title');
    $table->int('released');
    $table->foreign('format_id');
    $table->foreign('genre_id');
    $table->int('no_of_tracks');
    $table->foreign('condition_id');
}
```

This is what our database model looks like so far. We have the artists table and the records table,
we have added a foreign key constraint with a cascade on delete. 

<img class="w-full" src="/assets/images/records_table_step_eight.png" alt="Artists and records with constraints">

Before we move on with the tracks table we need to create the tables for formats, genres and conditions. 
These three tables will have the same structure, the only thing that differs is the table names.

This is the SQL for creating the table, just replace the `<table name>` 
with each of the table name you want to create.

```sql
CREATE TABLE <table name> (
   id int auto_increment primary key,
   name varchar(255),
   created_at timestamp,
   updated_at timestamp
);
```

If you are a Laravel user your migration would look something like this.

```php
Schema::create('<table name>', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
}
```

We should now have a model that looks like this.

<img class="w-full" src="/assets/images/artists_records_formats_genres_conditions.png" alt="Artists, records, formats, genres and conditions">

Now we need to connect the foreign keys together as we did with the artists and records tables.
The good thing is that we only need to update the records table like this.

In SQL

```sql
CREATE TABLE records( 
  id int auto_increment primary key,
  artist_id int,
  title varchar(255),
  released int,
  format_id int,
  genre_id int,
  no_of_tracks int,
  condition_id int,
  FOREIGN KEY (artist_id)
      REFERENCES artists (id)
      ON DELETE CASCADE,
  FOREIGN KEY (format_id)
      REFERENCES formats (id),
  FOREIGN KEY (genre_id)
      REFERENCES genres (id),
  FOREIGN KEY (condition_id)
      REFERENCES conditions (id)
);
```

And in Laravel the migration would look something like this.

```php
Schema::create('records', function (Blueprint $table) {
    $table->id();
    $table->foreign('artist_id')
        ->refrences('artists')
        ->on(id)
        ->onDelete('cascade');
    $table->string('title');
    $table->int('released');
    $table->foreign('format_id')
        ->references('formats')
        ->on('id');
    $table->foreign('genre_id')
        ->references('genres')
        ->on('id');
    $table->int('no_of_tracks');
    $table->foreign('condition_id')
        ->references('conditions')
        ->on('id');
}
```

As you probably noticed I didn't add any cascade on the foreign keys for the formats, genres and conditions tables.
This is because they don't really have that strong connections like artist and record. 
We could add them, but I don't see the need since the data in these will very seldom change, much less be deleted.  

<img class="w-full" src="/assets/images/artists_records_formats_genres_conditions_related.png" alt="Artists, records, formats, genres and conditions with relations">

## We now do the same for the tracks table.

* Track number
* Track titles
* Track mixes
* Track lengths
* Track artists

### Track number

> Can a track have more than one number?
>
> Yes!

> Can a number have more than one track?
>
> Yes!

Using a many to many here is a bit complicated since you also need to consider the record_id, 
so it would be a polymorphic many to many here as well just like we did with the many-to-many 
approach with the condition.

* record_id
* track_id
* track_number

I think that is overthinking it, so I'm happy to keep the track number in the tracks table.

### Track title

> Can a track have more than one title?
>
> Generally no, unless the track is renamed on the European release like Naughty By > Nature's `Ghetto Bastard` that was named `Everything's Gonna Be Alright` in 
> Europe.

> Can a track title have more than one track?
> 
> Yes, it could but the mix, length would probably differ.

Does this feel repetitive? Yep. Same as with the record title sticking with having it in the 
tracks table is the best approach in my opinion.

### Track mix

> Can a track have more than one mix?
>
> Yes, but not with the same track number

> Can a mix have more than one track?
>
> Yes, but not likely

I suggest sticking with keeping the mix column in the tracks table.

### Track length

> Can a track have different lengths?
>
> Yes, but it would also be a different mix of the track.

> Can a length have many tracks?
>
> Yes, but we would be overthinking it again.

Here I strongly suggest keeping the length in the tracks table.

We also need to connect the artists and the records table, and we do that with a foreign key named record_id.

We end up with a create script that looks something like this.

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

Laravel migration.

```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->int('track_no');
    $table->string('title');
    $table->string('mix');
    $table->string('length');
    $table->foreignId('record_id')
      ->constrained('records')
      ->onDelete('cascade');
    $table->timestamps();
}
```
As you might have noticed we haven't talked about the track artist and that is up next. 

### Track artist

> Can a track have more than one artist?
>
> Yes!

> Can an artist have more than one track?
>
> Yes!

Here we have a similar dilemma as with artist and record.

There are three approaches to the artist/track dilemma.

1. Use a many-to-many relation with a pivot table
2. Use the track artist for any additional (Featuring) artists.
3. Put the featuring artists in the tracks title.

This decision depends on how precise you need to be.

1. Very precise
2. Pretty precise
3. Not that precise

Approach two and three gives some issues with showing all tracks that is made or featured by given artist.
The best approach would be to extract the track artist to a pivot. We didn't use this approach in the records table
but, we could have done that and if you like you can make the necessary changes yourself.

* track_id
* artist_id

In a pivot table the created_at and the updated_at are quite optional, but I like to keep them there.
So let's create our pivot table.

```sql
CREATE TABLE artist_track(
   id int auto_increment primary key,
   artist_id int,
   track_id int,
   created_at timestamp,
   updated_at timestamp,
   FOREIGN KEY (artist_id) REFERENCES artists(id),
   FOREIGN KEY (track_id) REFERENCES tracks(id)
);
```

The Laravel migration.

```php
Schema::create('artist_track', function (Blueprint $table) {
    $table->id();
    $table->foreignId('artist_id')
      ->constrained('artists')
    $table->foreignId('track_id')
      ->constrained('tracks');
    $table->timestamps();
}
```
Notice the naming convention on the table name. You can name these almost anyway you like but by
following the Laravel standard for pivot tables (the table names in singular form and in alphabetic order).

The artists' table becomes artist and the tracks' table become tracks thus making the name artist_track. 
The main reason for following this standard is that the framework gives you some stuff for free.

So our tracks and our artist_track tables end up looking like this.

<img class="w-full" src="/assets/images/track_artist.png" alt="Tracks and artist_track tables">




## What we end up with

After our extraction exercise we end up with a database model that looks like this.

<img class="w-full" src="/assets/images/db_model.png" alt="The finished database model">

## Nullable columns

When it comes to nullable columns, SQL and Laravel Migrations differs a little. 
All the tables we have created with SQL has allowed a null value in all the columns except 
the id one but that is generated automatically. The ones we have created with a Laravel migration
does not allow for the value to be null unless we explicitly tell it to.

To make a column not to allow a null value with SQL you add the keywords `NOT NULL` to the column definition.

```sql
title varchar(255) NOT NULL,
```

This is a good practice to add to all columns that should never be allowed to contain a null value.

Laravel does this by default in its migrations and if you want the value to be nullable you need to define
that in your migration.

```php
$table->string('comment')->nullable();
```

One important thing is that a foreign key column never should be nullable, since I think it defeats the purpose
of having it in the first place.

So to make our table force us to enter values for all the required fields we need to update our creation scripts.

They will look like this after that update.

```sql
CREATE TABLE artists(
   id int auto_increment primary key,
   artist varchar(255) NOT NULL,
   created_at timestamp,
   updated_at timestamp
);

CREATE TABLE records(
    id int auto_increment primary key,
    artist_id int NOT NULL ,
    title varchar(255) NOT NULL,
    released int NOT NULL ,
    format_id int NOT NULL ,
    genre_id int NOT NULL ,
    no_of_tracks int NOT NULL ,
    condition_id int NOT NULL ,
    FOREIGN KEY (artist_id)
        REFERENCES artists (id)
        ON DELETE CASCADE,
        FOREIGN KEY (format_id)
        REFERENCES formats (id),
    FOREIGN KEY (genre_id)
        REFERENCES genres (id),
    FOREIGN KEY (condition_id)
        REFERENCES conditions (id)
);

-- The formats, genres and conditions tables
CREATE TABLE <table name> (
    id int auto_increment primary key,
    name varchar(255) NOT NULL,
    created_at timestamp,
    updated_at timestamp
    );

CREATE TABLE tracks(
   id int auto_increment primary key,
   track_no int NOT NULL ,
   title varchar(255) NOT NULL,
   mix varchar(255),
   length varchar(255) NOT NULL ,
   record_id int NOT NULL,
   created_at timestamp,
   updated_at timestamp,
   FOREIGN KEY (record_id) REFERENCES records(id)
       ON DELETE CASCADE
);
```

## Default values on columns

You can define default values on a column in case the record you are trying to insert doesn't provide one.
This is very useful if you have a column that almost always have the same value. Take out mix column in our
tracks table as an example, in 95% of the cases it would be `album mix` or something similar.
What we did in the Nullable section and set the mix column to nullable we can remove that and use a default 
for the column instead if we like.

It would look something like this.

```sql
CREATE TABLE tracks( 
  id int auto_increment primary key,
  track_no int NOT NULL ,
  title varchar(255) NOT NULL,
  mix varchar(255)NOT NULL DEFAULT('Album mix'),
  length varchar(255) NOT NULL ,
  track_artist varchar(255) NOT NULL,
  record_id int NOT NULL,
  created_at timestamp,
  updated_at timestamp, 
  FOREIGN KEY (record_id) REFERENCES records(id)
    ON DELETE CASCADE
);
```

Laravel Migration

```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->int('track_no');
    $table->string('title');
    $table->string('mix')->default('Album mix');
    $table->string('length');
    $table->string('track_artist');
    $table->foreignId('record_id')
      ->constrained('records')
      ->onDelete('cascade');
    $table->timestamps();
}
```


## Conclusion

I use this approach when I need to create a database model. However, I use TDD to drive my development and in this case I would have started with just two tables.

* Records
* Tracks

Then during my process I would extract the one-to-many and many-to-many relationships.
I might even extract one-to-one relationships if need be. If you have a users table, and you store
a lot of profile information in that table, information that you seldom display other than on the
profile page then consider moving it to a user_profiles table.

Another good way to check if something needs to be extracted is the visual test of your tables.

If some column has the same value over and over you should consider extracting it. 
When I say value here I didn't mean the foreign key ids. 

```
+----+----------------+----------------------+------+-------------------|
| id | author_name    | title                | part | series            | 
+----+----------------+----------------------+------+-------------------|
| 1  | Jordan, Robert | The Eye Of The World | 1    | The Wheel Of Time |
| 2  | Jordan, Robert | The Great Hunt       | 2    | The Wheel Of Time |
+----+----------------+----------------------+------+-------------------|
```

Consider this table.

* The author name is repeated, so it could be extracted.
* The series is repeating so it could be extracted.

The other columns can stay as is.

One thing you need to consider as well is that extraction of the columns can go to far and force you to make joins between too many tables and thus making your database model hard to understand.

So try to follow these rules

* Don't repeat yourself (too much)
* Keep it simple stupid (don't overcomplicate things)
* Extract when it makes sense (don't extract to absurdity)

Or in programmer acronyms

* DRY (Don't Repeat Yourself)
* KISS (Keep It Simple Stupid)
* EP (Extraction Principle (Probably made this one up, but it's still a valid point))

I hope this has given you some guidance on how to approach the building of a good database model.
It does in no way cover everything and every case since that would require several hundreds of pages.

What about performance and indexes?
Well, it's a big subject, and I think this post is long enough as is.
That just might come as a second part to this post.


## When to use json columns?

There are actually a few times that json columns are perfect and can be used for what I consider
them made for. Let's say that you do have an external API that you fetch thousands of records or that
you allow the user to upload json encoded data, for example in sales data or inventory data.
To make those request fast and as CPU friendly as possible you can store the json payload into a 
table with a json column temporarily and then schedule a job that processes the payload and inserts
them into other tables and columns.

//Tray2
