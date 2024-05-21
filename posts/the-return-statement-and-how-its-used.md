---
title: The return statement and how it's used
slug: the-return-statement-and-how-its-used
created_at: 2023-01-03
updated_at: 2023-01-03
published_at: 2023-01-03
author: Tray2
summary: return, what does it do, and how do you use it, are the things that I try to cover in this short post about the return statement.
---

I've been covering some pretty advanced database related topics here, but today we are going 
to go back to the absolute fundamentals of programming and talk about one of the most powerful 
keywords in any programming language, the `return`. It tells your program that it should stop executing
the function or method it is currently running and return anything that may or may not be between the 
`return` and the ending semicolon. This post uses php as the language of choice, but the principles are
the same in most programming languages out there.

So what does that mean? 

We will take a look at a few examples.

A return statement can return something, but it is not required, that means that both of these are
valid syntax.

```php
return $somevariable;

return;
```

In our first case study we use a simple if statement that returns a value depending on the outcome
of our comparison.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return 'Online';
  } else {
    return 'Offline';
  }
}
```

While this is a completely valid function, it has some unnecessary code that we can remove.

Remember this?

> It tells your program that it should stop executing the function or method it is currently running.

That means that any code after the return will never get executed, and in our case, if the status is
1 then the code after the first return will not be executed. That means that we can remove the `else` statement
and still have the same functionality, like so.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return 'Online';
  }
  
  return 'Offline';
}
```

That also means that you can't use multiple returns to return multiple values in the same function.
This would simply not work, and it would just return the string `Offline` if the status isn't equal to one.
The content of the variable status would never be returned, it's dead code.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return 'Online';
  }
  
  return 'Offline';
  return $status;
}
```

This would return `Offline` everytime the status isn't equal to one. So if you need to return more than one value,
you need to return a string with both values or an array.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return 'Online';
  }
  
  return 'Offline' . $status;
}
```

A string isn't always what you need, and then you can use an array to return multiple values.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return 'Online';
  }
  
  return ['Offline', $status];
}
```

However, in our case it's a bad practice since we then return two different data types.
So doing something like this would be better.

```php
function getServerStatusDescription($status)
{
  if ($status == 1) {
    return ['Online', $status];
  }
  
  return ['Offline', $status];
}
```

Now that we made sure that both cases returns the same datatype, we could take this on step further and
use something called a ternary operator, this has nothing to do with the return statement at all, but it's
still a valid option to have the same functionality in another (not necessarily better) way.

```php
function getServerStatusDescription($status)
{
  return [
    $status == 1 ? 'Online' : 'Offline',
    $status];
}
```

This is called a ternary operator, and it's a shorthand way to write an if statement.

Just as a bonus, I will show you a better more readable way than the ternary operator, and this is only
valid if you are using php 8.0 or higher, if you are running the 7.4 or older version of php, you really should
consider upgrading, since they all have reached end-of-life, and are no longer supported.

```php
function getServerStatusDescription($status)
{
    return match($status) {
        1 => ['Online', $status],
        default => ['Offline', $status]
    }
}
```

In the example above we only use two options in the `match` statement since we used that number of options in our
ternary example. There is nothing stopping you from adding more options to your `match` statement.
As we learned, a return stops execution of a function and returns the value, so every line of code after a return 
statement inside the same code block is dead code, that is also true when the return is inside a loop.

```php
$names = [
  'Carl',
  'Ed',
  'Michael',
  'Michelle',
  'Stephen'
];

foreach ($names as $name) {
  if (count(name) < 3 ) {
    return 'Name is too short';
  }
  echo name . '<br>';
}
```

The code above would stop running inside the second iteration, since `Ed` has less than three characters in his name, 
and we return if the number of characters is less than three.

There is a technique that I think is called `Early Return`, or at least something similar.
Many times when you write an if statement you do something like this

```php
if (! isEmpty($someValue)) {
  // do many lines of code
}
```

While there is nothing wrong with doing that, it is still very easy to miss the `!` aka `not` sign
when you are reading the code.

There are two ways (at least) to improve this code.

the first way is to create a new function that is called `isNotEmpty()`.

```php
if (isNotEmpty($someValue)) {
  // do many lines of code
}
```

The only objection I have to this is that you have an indented block of code inside your
function, so how can we handle that?

With an early return of course.

```php
if (isEmpty($someValue)) return;

// do many lines of code 
```

See, we don't need to negate, create another function, or indent our code, we just check if it is empty and return if it is.

I know what you are thinking, "but what about there being more than one online status?"

I think we all have written code like this more times than we would like to admit.


```php
if ($status == 1 ) {
  return 'Online';
} else if ($status == 2) {
  return 'Handling Request';
} else {
  return 'Offline';
}
```

We could of course use a switch statement here

```php
switch($status) {
  case 1: return 'Online';
  case 2: return 'Handling Request';
  default : return 'Offline';
}
```

They both do the same thing, and yes we don't need to use `break` when we use return.

No how can we avoid having multiple returns?

One way is only return once.


```php
function getServerStatusDescription($status)
{
  $serverStatus = 'Offline';
  
  if ($status == 1) {
    $serverStatus = 'Online';
  } else if ($status == 2) {
    $serverStatus = 'Handling Request';
  }
  
  return $serverStatus;
}
```

That also means that we can rewrite our first example as well.


```php
function getServerStatusDescription($status)
{
  $serverStatus = 'Offline';

  if ($status == 1) {
    $serverStatus = 'Online';
  }

  return $serverStatus;
}
```

Or if we are running an up to date php version we can use the `match` function, and remove the if statement completely,
since it can handle a more or less infinite number of values.  

As you can see, there are many ways to use `return` in your code.
Use it wisely, and don't add too much logic into each function.
It's much easier to understand a small five line function than a 50+ line one.

//Tray2
