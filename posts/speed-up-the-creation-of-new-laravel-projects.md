---
title: Speed up the creation of new Laravel projects
slug: speed-up-the-creation-of-new-laravel-projects
created_at: 2022-08-18
updated_at: 2022-08-18
published_at: 2022-08-18
author: Tray2
summary: Speed up the creation of new Laravel projects.
---
Speed up the creation of new Laravel projects.

**This works on Mac and has not been tested on Linux. The guide uses MySQL/MariaDB**

There are quite a few steps to take when you decide to create a new Laravel project.

1. Install Laravel using composer or the Laravel installer.
2. Enter the created directory.
3. Initiate a new local Git repository for your project.
4. Stage all files for your initial commit.
5. Commit your project to the local Git repository.
6. Log into your RDBMS and create a new database.
7. Update your `.env` file with the newly created database name and set your new applications name.

So how can we make all those steps simpler? We can create a shell script or an alias to help us, and how do we do that?
I'll use an alias that I add to my `.zsrc` file inside my home directory.

What we are trying to achieve here is to use a single command to do all the steps above, and be able to call it like so.

```shell
nlp my-new-laravel-project
```

I choose to call my alias nlp (New Laravel Project), you can choose another alias if you like.

When defining a normal alias, you can do this in your `.zsrc file`.

```shell
alias p='pwd'
```

Now since we need to do more than one thing in our alias we use a slightly different syntax, and create a function instead.

```shell
nlp()
{

}
```

Inside the curly bracers `{}` we define what we want the function to do.
We will follow the steps described above.

1. Install Laravel using composer.


```shell
nlp()
{
  composer create-project laravel/laravel "$1"
}
```

Running the `nlp` command now would install Laravel into the directory that is passed as the second parameter, while this
is all well and good, you might want to be able to install it from anywhere and not just the directory that you keep your projects.
There are a few ways to enable that, and the one I'm choosing is to utilize the `$HOME` variable. The variable 
contains the logged-in user's home directory, usually `/User/<username>`. So we need to update the command a little to 
make that happen.

```shell
nlp()
{
  composer create-project laravel/laravel "$HOME"/code/"$1"
}
```

I have chosen to store all my projects in a `code` directory inside my home directory.

2. Enter the project directory.

To enter the project directory we utilize the `cd` command.

```shell
nlp()
{
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
}
```

3. Initiate a new local Git repository for your project.

To initiate a new local Git repository you simply do just as you would from the terminal.

```shell
nlp()
{
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
}
```

4. Stage all files for your initial commit.

Every Git command are exactly the same as you would write them from the command line, so they don't require any explanation.

```shell
nlp()
{
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
}
```

5. Commit your project to the local Git repository.

```shell
nlp()
{
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
}
```

So now we have installed Laravel and set up everything we need to do with Git.

6. Log into your RDBMS and create a new database.

This step is a bit trickier and require that we use some variable in our shell script.

We start off with creating a variable containing the path to the mysql executable. We store that path in a readonly 
variable that we call MYSQL.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
}
```

We need another variable that contains the SQL needed to create the database once we are connected to the MySQL shell.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
}
```

We use the `IF NOT EXISTS` to only create the database if it does not already exist, and we name the database the same 
thing as our project.

Now we need to log in and execute our query, and since it's my local dev machine that only I have access to, I use the
default root user without any password. If you are planning to do something like this on a machine that is public, 
make sure that you pass additional parameters that contain the username and password, so you don't store them in the 
script.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
  $MYSQL -uroot -e "$Q1"
}
```

If you have some kind of shared host, and you need to create users and give the users access to certain commands and 
databases, you can just add additional query variables and execution lines.


7. Update your `.env` file with the newly created database name and set your new applications name.

The last two steps we need to do is to update our `.env` file to match our database and our applications name.
We use the `sed` command to replace the existing values. To find what we need to replace and what to replace it with, 
we use a kind of regular expression. The text between the first two `/` is the text we want to find and the text 
between the second pair is the text we want to replace it with.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
  $MYSQL -uroot -e "$Q1"
  sed -i -e "s/DB_DATABASE=laravel/DB_DATABASE=$1/g" .env
}
```

I think we can do multiple replaces at once, but since we want to transform the project name's first letter to 
uppercase, we do it in another `sed`. We also create a variable where we store the transformed project name.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  readonly APP_NAME="$(tr '[:lower:] '[:upper:] <<< ${1:0:1})${1:1}"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
  $MYSQL -uroot -e "$Q1"
  sed -i -e "s/DB_DATABASE=laravel/DB_DATABASE=$1/g" .env
}
```

The `tr` command translates the given string, and in our case we select the first letter in the project name, 
regardless if it's upper or lower case letter, and then pass it through the translator which makes it upper case. 
The `<<<` passes the content into the `tr`.

`readonly APP_NAME="$(tr '[:lower:] '[:upper:] <<< ${1:0:1})${1:1}"`

I used my considerable developer skills and stole the code straight from 
[Stackoverflow](https://stackoverflow.com/questions/12487424/uppercase-first-character-in-a-variable-with-bash).

So now we just need to change the app name in our `.env`.

```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  readonly APP_NAME="$(tr '[:lower:] '[:upper:] <<< ${1:0:1})${1:1}"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
  $MYSQL -uroot -e "$Q1"
  sed -i -e "s/DB_DATABASE=laravel/DB_DATABASE=$1/g" .env
  sed -i -e "s/APP_NAME=Laravel/APP_NAME=$APPNAME/g" .env
}
```

There is one more step required, and if we don't perform it, Git will think that we have added a file, which we don't
want it to do. For some reason that I haven't looked into, the `sed` command generates a backup file of our `.env` 
so we need to remove it.


```shell
nlp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="CREATE DATABASE IF NOT EXISTS $1"
  readonly APP_NAME="$(tr '[:lower:] '[:upper:] <<< ${1:0:1})${1:1}"
  composer create-project laravel/laravel "$HOME"/code/"$1"
  cd "$HOME"/code/"$1"
  git init
  git add .
  git commit -m "Initial commit"
  $MYSQL -uroot -e "$Q1"
  sed -i -e "s/DB_DATABASE=laravel/DB_DATABASE=$1/g" .env
  sed -i -e "s/APP_NAME=Laravel/APP_NAME=$APPNAME/g" .env
  rm .env-e
}
```

Now our `nlp` command to install and make the changes we want to our new project is complete, and we are ready to use it.

There are a lot of improvements to be done if we want to, and if this script is something I would share among others 
then I would add some validations and checks that everything is as it should be, before continuing.

I hope this small command will speed up your project creation in Laravel.

## Bonus command

We learned how to create projects, but what about deleting projects?

Of course, you can just do `rm -rf <project name>` from your command line, but what about the database?
Logging in and manually dropping it is not that hard, but wouldn't a `klp` (Kill Laravel Project) be nice?

I think it would so let's create that.

```shell
klp()
{
  readonly MYSQL=`which mysql`
  readonly Q1="DROP DATABASE IF EXISTS $1;"
  cd "$HOME"/code
  rm -rf "$1"
  $MYSQL -uroot -e "$Q1"
}
```

## Conclusion

I'm far from an expert at shell scripting, and I'm well aware that there is room for improvement of the scripts, 
specially where it comes to feed back and validation. 

//Tray2




