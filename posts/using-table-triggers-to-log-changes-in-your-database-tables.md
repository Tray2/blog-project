---
title: Using table triggers to log changes in your database tables.
slug: using-table-triggers-to-log-changes-in-your-database-tables
created_at: 2022-10-22
updated_at: 2022-10-22
published_at: 2022-10-22
author: Tray2
summary: Tracking changes in a table or several tables for that matter can be essential for your application, and we are going to take a look on how to do that using only the MySQL/MariaDB database. 
---
Tracking changes in a table or several tables for that matter can be essential for your application,
and we are going to take a look on how to do that using only the MySQL/MariaDB database. The examples will be using
Laravel, but the syntax for creating tiggers is purely SQL so it is language and framework agnostic.

We have been tasked to log all the changes made to an `orders` table in an e-commerce application built with Laravel,
and we need to track these events.

1. Order creation
2. Packing started
3. Packing finished
4. Prepared for shipping
5. Shipped
6. Received

One way to do this would be to use Event(s) in Laravel to track all these changes, but I think a better
solution would be to let the database do all the heavy lifting. What I mean by that is that instead of
registering an event that we fire each time we do any of the above, we let the database trigger those
events instead. We will be using something that is called `Triggers` or `Table triggers`, you can register
one or more triggers on a table that does different things depending on the action being performed on the
table that has the trigger.

The actions we can tell the table to trigger on are `INSERT`, `UPDATE` and `DELETE`, we can also specify
when we should trigger the event, `BEFORE` or `AFTER` the action. The `before` can manipulate the data being
inserted or updated, before it is stored in the table, and the `after` allows us to do something after it has
been stored in the table. We will be using `AFTER` since we are talking about logging changes made to a table.

The syntax to create a trigger is
```sql
CREATE TRIGGER <trigger_name> <BEFORE|AFTER> <ACTION> 
    ON <table_name> FOR EACH ROW
BEGIN
    /* What the trigger should do */
END;
```

In MySQL 8 you can create a multiple action trigger in one go, in earlier versions you need to specify them one by one.

So let's start with the trigger for when an order is created (inserted).

```sql
CREATE TRIGGER insert_order_trigger AFTER INSERT
    ON orders FOR EACH ROW
BEGIN
    INSERT INTO order_logs(action, order_id, old_status, new_status, created_at, updated_at)
        VALUES('INSERT', NEW.id, NULL, NEW.status, NOW(), NOW());
END;
```

The values are stored in a variable called `NEW`, and from that variable we can get all the columns
that was inserted into the orders table, the values are accessed with the dot syntax. For simplicity’s
sake, we are just storing the `id` and the `status` of order and when it happened. We are also storing
the previous status, so that we can see if one of the steps has been skipped, or if they needed to start over
with the order. The old value for an `insert` is null.

So let's move on to the `update` action, the syntax will be almost the same.

```sql
CREATE TRIGGER update_order_trigger AFTER UPDATE
    ON orders FOR EACH ROW
BEGIN
    INSERT INTO order_logs(action, order_id, old_status, new_status, created_at, updated_at)
        VALUES('UPDATE', NEW.id, OLD.status, NEW.status, NOW(), NOW());
END;
```

The difference other than the name of the trigger and the action, is that we use the `OLD` variable to
get the status of the order before we did the update, that way we will see the order going from status `created` to
`packing started`.

However before we can do anything with the trigger code, we need to create the `orders` table.
We don't really care about anything more since this is just a demonstration of how triggers work.

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->integer('status');
        $table->foreignIdFor(User::class);
        $table->timestamps();
    });
}
```

The code above creates our `orders` table, so now we can add the triggers, and we do that in the `up` method
of our migration, and since there is no native support in Laravel for creating triggers, we will have to
use the `DB` facade to create them. To keep this brief I will also create the `order_logs` table in the same migration.
It is very important that both `orders` and `order_logs` exists before the triggers are created.

```php
public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->integer('status');
        $table->foreignIdFor(User::class);
        $table->timestamps();
    });

    Schema::create('order_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->string('action');
        $table->integer('old_status')->nullable();
        $table->integer('new_status');
        $table->timestamps();
    });
    
    DB::statement("CREATE TRIGGER insert_order_trigger AFTER INSERT
                   ON orders FOR EACH ROW
                   BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, created_at, updated_at)
                     VALUES('INSERT', NEW.id, NULL, NEW.status, NOW(), NOW());
                   END;
                ");
    DB::statement("CREATE TRIGGER update_order_trigger AFTER UPDATE
                   ON orders FOR EACH ROW
                   BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, created_at, updated_at)
                     VALUES('UPDATE', NEW.id, OLD.status, NEW.status, NOW(), NOW());
                   END;
                ");
}
```

Before we attempt to run our migration we need to take a look at the `down` method in our migration file, since we both
created another table, and two table triggers. That means that we need to drop those as well when we do a rollback.

```php
public function down(): void
{
    DB::statement('DROP TRIGGER IF EXISTS insert_order_trigger;');
    DB::statement('DROP TRIGGER IF EXISTS update_order_trigger;');
    Schema::dropIfExists('orders');
    Schema::dropIfExists('order_logs');
}
```

After we have migrated the database we can take a look at it using `SHOW TABLES` command.

```shell
MariaDB [log_demo]> show tables;
+------------------------+
| Tables_in_log_demo     |
+------------------------+
| failed_jobs            |
| migrations             |
| order_logs             |
| orders                 |
| password_resets        |
| personal_access_tokens |
| users                  |
+------------------------+
7 rows in set (0.014 sec)
```

We have our two tables, `orders` and `order_logs`. We can also check that our triggers are created properly by
using the `SHOW TRIGGERS` command.

```shell
MariaDB [log_demo]> show triggers like 'orders%'\G;
*************************** 1. row ***************************
             Trigger: insert_order_trigger
               Event: INSERT
               Table: orders
           Statement: BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, created_at)
                     VALUES('INSERT', NEW.id, NULL, NEW.status, NOW());
                   END
              Timing: AFTER
             Created: 2022-10-05 05:33:34.01
            sql_mode: ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
             Definer: root@localhost
character_set_client: utf8mb4
collation_connection: utf8mb4_unicode_ci
  Database Collation: utf8mb4_general_ci
*************************** 2. row ***************************
             Trigger: update_order_trigger
               Event: UPDATE
               Table: orders
           Statement: BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, created_at)
                     VALUES('UPDATE', NEW.id, OLD.status, NEW.status, NOW());
                   END
              Timing: AFTER
             Created: 2022-10-05 05:33:34.14
            sql_mode: ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
             Definer: root@localhost
character_set_client: utf8mb4
collation_connection: utf8mb4_unicode_ci
  Database Collation: utf8mb4_general_ci
2 rows in set (0.009 sec)
```

A lot of information there, but the only parts we need to care about for now, is that we have two rows, and that
they have the events, `UPDATE` and `INSERT`.

Now we can try it out by creating an order. I assume that you know how to create 
an `Order` model and a factory for it, so I will not go into that.

```php
Order::factory()->create([
    'status' => 1,
    'user_id' => 1,
]);
```

So if we have done everything correctly, we should have an order in our `orders` table, and we should have a record
in our order_logs table with the `INSERT` action. Let's take a look.

```shell
MariaDB [log_demo]> select * from orders;
+----+--------+---------+---------------------+---------------------+
| id | status | user_id | created_at          | updated_at          |
+----+--------+---------+---------------------+---------------------+
|  1 |      1 |       1 | 2022-10-05 03:54:44 | 2022-10-05 03:54:44 |
+----+--------+---------+---------------------+---------------------+
1 row in set (0.000 sec)
```

We do have one order with status 1 as expected, how about the log entry?

```shell
MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | created_at          | updated_at          |
+----+----------+--------+------------+------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | 2022-10-05 05:54:44 | 2022-10-05 05:54:44 |
+----+----------+--------+------------+------------+---------------------+---------------------+
1 row in set (0.000 sec)
```

Now isn't that really cool, or is it just me who is amazed by what the database can do for us?

So what about updates, we will test that now. We will make updates to the order for each of
the statuses that we talked about earlier. 

```php
$order = Order::findOrFail(1);
$order->status = 2;
$order->save();  
```

Now that we have updated the status let's take a look in our two tables.

```shell
MariaDB [log_demo]> select * from orders;
+----+--------+---------+---------------------+---------------------+
| id | status | user_id | created_at          | updated_at          |
+----+--------+---------+---------------------+---------------------+
|  1 |      2 |       1 | 2022-10-05 03:54:44 | 2022-10-05 16:34:30 |
+----+--------+---------+---------------------+---------------------+
1 row in set (0.000 sec)

MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | created_at          | updated_at          |
+----+----------+--------+------------+------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | 2022-10-05 05:54:44 | 2022-10-05 05:54:44 |
|  2 |        1 | UPDATE |          1 |          2 | 2022-10-05 18:34:30 | 2022-10-05 18:34:30 |
+----+----------+--------+------------+------------+---------------------+---------------------+
2 rows in set (0.000 sec)
```

Pretty sweet right? I won't make you read through me updating every status one by one, but when the order 
has reached the status `delivered` the `order_logs` should look something like this.

```shell
MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | created_at          | updated_at          |
+----+----------+--------+------------+------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | 2022-10-05 05:54:44 | 2022-10-05 05:54:44 |
|  2 |        1 | UPDATE |          1 |          2 | 2022-10-05 18:34:30 | 2022-10-05 18:34:30 |
|  3 |        1 | UPDATE |          2 |          3 | 2022-10-05 18:39:59 | 2022-10-05 18:39:59 |
|  4 |        1 | UPDATE |          3 |          4 | 2022-10-05 18:40:03 | 2022-10-05 18:40:03 |
|  5 |        1 | UPDATE |          4 |          5 | 2022-10-05 18:40:08 | 2022-10-05 18:40:08 |
|  6 |        1 | UPDATE |          5 |          6 | 2022-10-05 18:40:12 | 2022-10-05 18:40:12 |
+----+----------+--------+------------+------------+---------------------+---------------------+
6 rows in set (0.000 sec)
```

We have now accomplished the task we were given, however they aren't fully satisfied with the result,
they also realized that they need to know who has done each step. This presents a bit of a challenge,
not because the code is really complex to get the database user, but rather that all connections done 
to the database by our application uses the same user.

Let's start by adding a `changed_by` column to our `order_logs` table, and update the triggers accordingly.
We use the `USER()` function to get the user from MySQL.

```php
Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('action');
            $table->integer('old_status')->nullable();
            $table->integer('new_status');
            $table->string('changed_by');
            $table->timestamps();
        });

        DB::statement("CREATE TRIGGER insert_order_trigger AFTER INSERT
                   ON orders FOR EACH ROW
                   BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, changed_by, created_at, updated_at)
                     VALUES('INSERT', NEW.id, NULL, NEW.status, USER(), NOW(), NOW());
                   END;
                ");
        DB::statement("CREATE TRIGGER update_order_trigger AFTER UPDATE
                   ON orders FOR EACH ROW
                   BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, changed_by, created_at, updated_at)
                     VALUES('UPDATE', NEW.id, OLD.status, NEW.status, USER(), NOW(), NOW());
                   END;
                ");
```

Now let's insert an order into the `orders` table just like we did before, and do a select on the `order_logs`.

```shell
MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | changed_by     | created_at          | updated_at          |
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | root@localhost | 2022-10-05 18:55:11 | 2022-10-05 18:55:11 |
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
1 row in set (0.000 sec)
```

Hey wait a minute, we sure as hell doesn't want to have the database user here, we want the application user,
Well we can try changing the `USER()` to `CURRENT_USER()` since according to the internet, they can give different
results, so let's give it a try.

```shell
MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | changed_by     | created_at          | updated_at          |
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | root@localhost | 2022-10-05 19:02:17 | 2022-10-05 19:02:17 |
+----+----------+--------+------------+------------+----------------+---------------------+---------------------+
1 row in set (0.000 sec)
```

No such luck, this is due to the fact that there is a big difference between a database user and an application user.
There area few ways to get around this, and we will look at two options, one bad and one pretty good. 
Let's start with the bad one.

We could create a database user for each user on our system and change the user who connects to the database, depending
on who is logged in. While this would work great inside the database, it would generate unneeded complexity to our
application on the php side. 

The better solution would be to store the user who update the order in the `orders` table.
Let's give that a go shall we?

We start with the migrations and add the `changed_by` column to the `orders` table as well.

```php
Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('status');
            $table->foreignIdFor(User::class);
            $table->string('changed_by');
            $table->timestamps();
        });
```

We also need to update our triggers.

```php
DB::statement("CREATE TRIGGER insert_order_trigger AFTER INSERT
                   ON orders FOR EACH ROW
                   BEGIN
                     INSERT INTO order_logs(action, order_id, old_status, new_status, changed_by, created_at, updated_at)
                     VALUES('INSERT', NEW.id, NULL, NEW.status, NEW.changed_by, NOW(), NOW());
                   END;
                ");
DB::statement("CREATE TRIGGER update_order_trigger AFTER UPDATE
           ON orders FOR EACH ROW
           BEGIN
             INSERT INTO order_logs(action, order_id, old_status, new_status, changed_by, created_at, updated_at)
             VALUES('UPDATE', NEW.id, OLD.status, NEW.status, NEW.changed_by, NOW(), NOW());
           END;
        ");
```

Now after we migrated again we need to change the way we create the record so that we get the authenticated user.
We create a user so that we can pass that user's name to the `orders` table.

```php
$user = User::factory()->create();
Order::factory()->create([
  'status' => 1,
  'user_id' => 1,
  'changed_by' => $user->name,
]);
```

Now if we look at the `orders` and `order_logs` tables we see this.

```shell
MariaDB [log_demo]> select * from orders;
+----+--------+---------+-----------------+---------------------+---------------------+
| id | status | user_id | changed_by      | created_at          | updated_at          |
+----+--------+---------+-----------------+---------------------+---------------------+
|  1 |      1 |       1 | Susana Donnelly | 2022-10-05 17:31:22 | 2022-10-05 17:31:22 |
+----+--------+---------+-----------------+---------------------+---------------------+
1 row in set (0.000 sec)

MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+-----------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | changed_by      | created_at          | updated_at          |
+----+----------+--------+------------+------------+-----------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | Susana Donnelly | 2022-10-05 19:31:22 | 2022-10-05 19:31:22 |
+----+----------+--------+------------+------------+-----------------+---------------------+---------------------+
1 row in set (0.000 sec)
```

This is what we wanted, however the `changed_by` should be the username and not the user's name, but that is something
I will not cover in this post since it has nothing to do with the triggers at all. The reason for putting the name in 
plain text instead of using a foreign key, is that I don't want to have any relationships with the `order_logs` table,
this is my preference, and you may do as you see fit.

Now let's update the order. I created a new user for each step just to prove the concept, and I made all the steps
behind the scenes.

```php
$user = User::factory()->create();

$order = Order::findOrFail(1);
$order->status = 6;
$order->changed_by = $user->name;
$order->save();
```

You should of course use `Auth::user()->name` when creating and updating the orders.

```shell
MariaDB [log_demo]> select * from order_logs;
+----+----------+--------+------------+------------+--------------------+---------------------+---------------------+
| id | order_id | action | old_status | new_status | changed_by         | created_at          | updated_at          |
+----+----------+--------+------------+------------+--------------------+---------------------+---------------------+
|  1 |        1 | INSERT |       NULL |          1 | Susana Donnelly    | 2022-10-05 19:31:22 | 2022-10-05 19:31:22 |
|  2 |        1 | UPDATE |          1 |          2 | Demarcus Mueller   | 2022-10-05 19:45:54 | 2022-10-05 19:45:54 |
|  3 |        1 | UPDATE |          2 |          3 | Lavina Stroman     | 2022-10-05 19:45:58 | 2022-10-05 19:45:58 |
|  4 |        1 | UPDATE |          3 |          4 | Rosalyn Mertz Jr.  | 2022-10-05 19:46:02 | 2022-10-05 19:46:02 |
|  5 |        1 | UPDATE |          4 |          5 | Carmel Hand        | 2022-10-05 19:46:08 | 2022-10-05 19:46:08 |
|  6 |        1 | UPDATE |          5 |          6 | Catalina VonRueden | 2022-10-05 19:46:13 | 2022-10-05 19:46:13 |
+----+----------+--------+------------+------------+--------------------+---------------------+---------------------+
6 rows in set (0.000 sec)
```

Now ain't that grand, we can see who made each step, and even if we need to revert to a previous status, it will be
logged, and we will know who did it and when it was done. 

If there are more than one table that you want to log all the actions in, it might not be the best of ideas to create
a log table for each table, but rather create one log table that is generic enough to handle any table you throw at it.
Let's give that a try.

We start with creating the `logs` table.

```php
Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('column_name');
            $table->unsignedBigInteger('primary_key');
            $table->integer('old_value')->nullable();
            $table->integer('new_value')->nullable();
            $table->string('action');
            $table->string('changed_by');
            $table->timestamps();
        });
```

As you can see there are some differences between the `order_logs` table and the new one.
Since the entry can come from any table we need to know which table the record belongs to, and we also need to know 
which column was changed, and last but not least we need to know which primary key it refers to.

Next up is to update our triggers to use the new `logs` table.
We will still be using the `orders` table in our example.

```php
    DB::statement("CREATE TRIGGER insert_order_trigger AFTER INSERT
               ON orders FOR EACH ROW
               BEGIN
                 INSERT INTO logs(table_name, column_name, primary_key, old_value, new_value, action, changed_by, created_at, updated_at)
                 VALUES('ORDERS', 'id', NEW.id, NULL, NEW.id, 'INSERT', NEW.changed_by, NOW(), NOW());
                 INSERT INTO logs(table_name, column_name, primary_key, old_value, new_value, action, changed_by, created_at, updated_at)
                 VALUES('ORDERS', 'status', NEW.id, NULL, NEW.status, 'INSERT', NEW.changed_by, NOW(), NOW());
               END;
            ");
    DB::statement("CREATE TRIGGER update_order_trigger AFTER UPDATE
               ON orders FOR EACH ROW
               BEGIN
                 INSERT INTO logs(table_name, column_name, primary_key, old_value, new_value, action, changed_by, created_at, updated_at)
                 VALUES('ORDERS', 'id', OLD.id, OLD.id, OLD.id, 'UPDATE', NEW.changed_by, NOW(), NOW());
                 INSERT INTO logs(table_name, column_name, primary_key, old_value, new_value, action, changed_by, created_at, updated_at)
                 VALUES('ORDERS', 'status', OLD.id, OLD.status, NEW.status, 'UPDATE', NEW.changed_by, NOW(), NOW());
               END;
            ");
```

I know it's a bit silly to log the `id` column since it will never ever change, but please just roll with the example.
It would have been even worse to log the `changed_by` column. You will need to create an `insert` statement for each column
that you want to track the changes for.

Now lets run create one order and see if our insert trigger works.

```php
$user = User::factory()->create();
Order::factory()->create([
  'status' => 1,
  'user_id' => 1,
  'changed_by' => $user->name,
]);
```

This is the result of that creation.

```shell
MariaDB [log_demo]> select * from orders;
+----+--------+---------+---------------+---------------------+---------------------+
| id | status | user_id | changed_by    | created_at          | updated_at          |
+----+--------+---------+---------------+---------------------+---------------------+
|  1 |      1 |       1 | Parker O\'Hara | 2022-10-21 17:47:26 | 2022-10-21 17:47:26 |
+----+--------+---------+---------------+---------------------+---------------------+
1 row in set (0,000 sec)

MariaDB [log_demo]> select * from logs;
+----+------------+-------------+-------------+-----------+-----------+--------+---------------+---------------------+---------------------+
| id | table_name | column_name | primary_key | old_value | new_value | action | changed_by    | created_at          | updated_at          |
+----+------------+-------------+-------------+-----------+-----------+--------+---------------+---------------------+---------------------+
|  1 | ORDERS     | id          |           1 |      NULL |         1 | INSERT | Parker O\'Hara | 2022-10-21 19:47:26 | 2022-10-21 19:47:26 |
|  2 | ORDERS     | status      |           1 |      NULL |         1 | INSERT | Parker O\'Hara | 2022-10-21 19:47:26 | 2022-10-21 19:47:26 |
+----+------------+-------------+-------------+-----------+-----------+--------+---------------+---------------------+---------------------+
2 rows in set (0,000 sec)
```

So what then happens if we run the status updates two through to six?

```shell
MariaDB [log_demo]> select * from logs;
+----+------------+-------------+-------------+-----------+-----------+--------+---------------------+---------------------+---------------------+
| id | table_name | column_name | primary_key | old_value | new_value | action | changed_by          | created_at          | updated_at          |
+----+------------+-------------+-------------+-----------+-----------+--------+---------------------+---------------------+---------------------+
|  1 | ORDERS     | id          |           1 |      NULL |         1 | INSERT | Parker O\'Hara      | 2022-10-21 19:55:44 | 2022-10-21 19:55:44 |
|  2 | ORDERS     | status      |           1 |      NULL |         1 | INSERT | Parker O\'Hara      | 2022-10-21 19:55:44 | 2022-10-21 19:55:44 |
|  3 | ORDERS     | id          |           1 |         1 |         1 | UPDATE | Gus Spinka          | 2022-10-21 19:55:58 | 2022-10-21 19:55:58 |
|  4 | ORDERS     | status      |           1 |         1 |         2 | UPDATE | Gus Spinka          | 2022-10-21 19:55:58 | 2022-10-21 19:55:58 |
|  5 | ORDERS     | id          |           1 |         1 |         1 | UPDATE | Sadye Predovic      | 2022-10-21 19:56:02 | 2022-10-21 19:56:02 |
|  6 | ORDERS     | status      |           1 |         2 |         3 | UPDATE | Sadye Predovic      | 2022-10-21 19:56:02 | 2022-10-21 19:56:02 |
|  7 | ORDERS     | id          |           1 |         1 |         1 | UPDATE | Dimitri Kohler      | 2022-10-21 19:56:05 | 2022-10-21 19:56:05 |
|  8 | ORDERS     | status      |           1 |         3 |         4 | UPDATE | Dimitri Kohler      | 2022-10-21 19:56:05 | 2022-10-21 19:56:05 |
|  9 | ORDERS     | id          |           1 |         1 |         1 | UPDATE | Courtney Mraz       | 2022-10-21 19:56:10 | 2022-10-21 19:56:10 |
| 10 | ORDERS     | status      |           1 |         4 |         5 | UPDATE | Courtney Mraz       | 2022-10-21 19:56:10 | 2022-10-21 19:56:10 |
| 11 | ORDERS     | id          |           1 |         1 |         1 | UPDATE | Kathryne Gleason MD | 2022-10-21 19:56:16 | 2022-10-21 19:56:16 |
| 12 | ORDERS     | status      |           1 |         5 |         6 | UPDATE | Kathryne Gleason MD | 2022-10-21 19:56:16 | 2022-10-21 19:56:16 |
+----+------------+-------------+-------------+-----------+-----------+--------+---------------------+---------------------+---------------------+
12 rows in set (0,000 sec)
```

You can do a lot more with table triggers if you like, you just have to learn a little about the programming language
of the database, I'm not sure what it is actually called for MySQL/MariaDB, but for Oracle databases it's called
PL/SQL and for Microsoft SQLServer it's called T-SQL. I suggest doing a Google search on "MySQL stored procedures", and of course
do some more reading about tabletriggers, since we only scratched the surface on what you can do with them.

I hope you enjoyed this post, and as usual a big thank you to [@rsinnbeck](https://twitter.com/@rsinnbeck) for the help 
on editing.

//Tray2
