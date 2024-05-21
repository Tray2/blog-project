---
title: Testing your DOM in Laravel.
slug: testing-your-dom-in-laravel
created_at: 2022-11-10
updated_at: 2022-11-10
published_at: 2022-11-10
author: Tray2
summary: Testing your views, yes I said, testing your views. This is something that is both boring and quite tricky to get right.
---

Testing your views, yes I said, testing your views. This is something that is both boring and quite tricky to get right.
Sure you can write a test that checks that a certain text is shown, or not shown according to what you want to show
in your view. The reason for it being tricky to test your views is that the view is something that you as a backend 
developer doesn't really have that much control over, unless of course if you are a one-man team doing the full stack.

Let's look at an example. You will have to excuse that I don't use PHPUnit in this post, but I'm trying to learn how
to use PEST, and so far I really like it. I was a bit of a sceptic about using closures, but I have grown quite fond 
of PEST. However, this isn't a post about PEST, but rather about testing your views, or rather the DOM of your views.

Let's say that we want to test if the view contains a table. We could write the test like this.

```php
it('has a table', function () {
    get(route('records'))
        ->assertOk()
        ->assertSee([
            '<table>',
            '</table>'
        ], false);
});
```

Then in our view we would have code similar to this.

```php
<table>
    <tr>
        <th>Artist:</th>
        <th>Record Title:</th>
        <th>Release Year:</th>
    </tr>
    @foreach($records as $record)
        <tr>
            <td>{{ $record->artist }}</td>
            <td>{{ $record->title }}</td>
            <td>{{ $record->pelease_year }}</td>
        </tr>
    @endforeach
</table>
```

This is all well and good, but what if our frontend persons adds classes to our table?

```php
<table class="some_class_making_it_pretty">
```

Our test would no longer pass. Some might argue that you shouldn't really test for the structure of the html,
but sometimes you need to. Maybe not for simple things like `divs`, `paragraphs`, `spans` or any of the multitude of 
html elements that you could use, but rather things like the image we are displaying has the accessibility `alt` 
property set. The thing we really should test is our forms, but that can be really tricky to do, sure you can just 
check that there is an `input`, a `name` property that equals a column in our database, but ...

```php
it('has a input named some_database_column', function () {
    get(route('records.create'))
        ->assertOk()
        ->assertSee([
            'input',
            'name="some_database_column"'
        ], false);
});
```

How would we know that we test the right input? 
We don't, sure we could do something like this.

```php
it('has a table', function () {
    get(route('records.create'))
        ->assertOk()
        ->assertSee([
            '<input name="some_database_column"'
        ], false);
});
```

That would make sure that we test the correct input, but we are back in the hands of the frontend person. What if
they change the order of the properties, well then we are screwed. 

What about input values? 
Same thing there, we can check for its existence, but we can't really be sure that the value isn't in the wrong form
element. You could probably come up with some solution using regular expressions, and make that work for most cases, but
I promise you that you will never get them all, there will always be edge cases.

So what can we do? 
We could of course ignore the issue completely, we could be satisfied with the checks we can do,
I was chatting on Twitter with [@rsinnbeck](https://twitter.com/@rsinnbeck) about the voes of testing forms, to make 
sure they contain the right elements, the labels for those elements, the CSRF tokens, and so on. 
A few days later he started developing a package to help with testing the DOM, and it has just been made public, 
and published on `Packigist`. The package is called `laravel-dom-assertions` and at the time of writing is in 
version 1.1.1.

Let's pull it in as a dev dependency.

`composer require sinnbeck/laravel-dom-assertions --dev`

How will this package help us?
It helps us partly by giving us a pretty syntax, and providing us with a simple way to read the DOM, the elements and
their attributes. 

## Testing the DOM.

Let's start with something simple, like checking if the page contains an element of a certain type.
A recommendation is to always use the `->assertOk()` before you do any other assertions, this is to avoid any false 
positives. 

```php
get('/records')
    ->assertOk()
    ->assertElementExists();
```

The `assertElementExists()` without any parameters looks for the `body` tag in your view,
so if we run the test above on our empty view, we will get the following result.

```shell
The view is empty!
Failed asserting that a string is not empty.
```

So let's try to make it pass.

```html
<body></body>
```

```shell
✓ it has a body tag

Tests:  1 passed
Assertions:  1
```

Testing if the page has a `body` tag might not be the most useful test, but it is a good start. 
It is not proper html since we are missing the `doctype`, `header`, `title` and a few `meta` tag,
but it makes the test pass. Hopefully it will also check for those things in a later version, 
or at least have methods for it.

There is a small caveat for this, it will wrap the html in a body tag if one is missing, just like your browser would.
I personally think this is wrong, and it should be strict with lazy developers who write improper html. I know that 
[@rsinnbeck](https://twitter.com/rsinnbeck) is still looking for a way to prevent the DOMDocument to do that, so if you
know how to fix it please make a PR at the GitHub [repository](https://github.com/sinnbeck/laravel-dom-assertions)

The `assertElementExists()` method takes a parameter that is the CSS selector of the element you want
to assert is there.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#main');
```

Running the test will tell us the following

```shell
No element found with selector: #main
```

So let's update the view so that it has a `#main`.

```html
<body>
    <div id="main">

    </div>
</body>
```

The test is once again passing.

```shell
✓ it has a #main element

Tests:  1 passed
Assertions:  1
```

You can use any valid CSS selector, like

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#main') //Id selector
    ->assertElementExists('.some-class') //Class selector
    ->assertElementExists('p')  //Tag selector
    ->assertElementExists('div > p'); //Nested selector
```

Basically any CSS selector that you can imagine.

Let's make the test above pass, just to see that it works.

```html
<body>
    <div id="main" class="some-class">
        <p></p>
    </div>
</body>
```


Now just testing for the presence of an element with an id of `main` is pretty pointless, so let's expand on it
to make sure that it is of the correct tag type. We want to make sure that the `main` id is a `div`, and this is 
how we do it.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#main', function (AssertElement $element) {
        $element->is('div');
    });
```

Since we have a div with the id of `main` already our test passes with flying colors.

```shell
✓ it has a #main element that is a div

Tests:  1 passed
Assertions:  4
```

We can also test if the element has a certain attribute.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#main', function (AssertElement $element) {
        $element->is('div')
        ->has('class');
    });
```

Now checking that an element has a class attribute isn't very useful, but if you for example use a JavaScript framework
like AlpineJs, then it would be good to check for attributes related to that. This will look for an element with the
id of `overview`, and the property `x-data` with the value of `{foo: 1}`

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->has('x-data', '{foo: 1}');
    });
```

The html would then something look like this.

```html
<div id="overview" class="some-nifty-class" x-data="{foo: 1}"></div>
```

We could even check for which CSS classes that are attached to the element if we want. This would be a very bad
practice, but it is possible, however for each class you add to your element, the `->has('class', '<classes>'` would
need to have the same classes in both your test and view. The only time I think it's valid to test for a class on 
an element is when you check the state of it. If it's `active`, `hidden`, or some other state that you need to check.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->has('x-data', '{foo: 1}')
        ->has('class', 'some-nifty-class');
    });
```

We can chain an infinite number of attribute checks if we want to, or write them on their own line.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->has('x-data', '{foo: 1}')
        ->has('x-something', '{something: 3}');
    });
```

We can also chain an infinite number of `assertElement()` if we want to.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->has('x-data', '{foo: 1}')
        ->has('x-something', '{something: 3}');
    })
    ->assertElementExists('#underview', function (AssertElement $element) {
        $element->has('x-data', '{foo: 2}')
        ->has('x-something', '{something: 4}'););
```

Let's move on shall we, and take a look at how to check that an element contains another element 
AKA have a child-element. This test would look for an element with the id of `overview` and then a div 
inside that element.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->contains('div');
    });
```

You can use any CSS selector in the `contains` that you need.
Take this test straight from the docs for example.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->contains('div:nth-of-type(3)');
    });
```

It will look for the third child `div` of the "`overview`" element.

What about the child element having a certain attribute?
[@rsinnbeck](https://twitter.com/rsinnbeck) has you covered there as well, just pass a second argument of type array 
to the `->contains` method.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#overview', function (AssertElement $element) {
        $element->contains('li.list-item', [
            'x-data' => 'foobar'
        ]);
    });
```

We can also use the `doesntContain()` method to make sure that a that doesn't have a certain child element. Same as for
`contains` we can pass the attributes as a second parameter.

We can check for multiple child elements of the same type as so.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#some-list', function (AssertElement $element) {
        $element->contains('li', 3);
    });
```

The code above would look for this html

```html
<ul id="some-list">
    <li>Item 1</li>
    <li>Item 2</li>
    <li>Item 3</li>
</ul>
```

If you have more than three list items the assertion will fail your test.

If you want to target a specific child element you can use the `find()` method like so.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#some-list', function (AssertElement $element) {
        $element->find('li.list-item');
    });
```

If more than one child element is found, it will use the first one, so you need to be specific when using the 
`find()` method.

You can make further assertions on the element you found if you want to, just pass a new closure as the second argument
to the `find()` method.

```php
get('/records')
    ->assertOk()
    ->assertElementExists('#some-list', function (AssertElement $element) {
        $element->find('li.list-item', function (AssertElement $element) {
            $element->is('li');
        });
    });
```

This means that you can add how many levels as you like when asserting the DOM, but be aware that it becomes quite
messy with too many levels in the same test.

Now on to the most interesting part of the Laravel-dom-assertion package, testing forms.

## Testing forms.

When testing forms we could use the `->assertElementExists()` method, but for our convenience there is a more 
descriptive helper method called `->assertFormExists()`. If you don't pass any parameters it will check for the
existence of any form in our DOM. To assert that a particular form exists, just pass the selector of choice to the 
method.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form');
```

We can then as a second argument to the `assertFormExists()` method pass a closure that receives an instance of the
`AssertForm` class like so.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
    
    });
```

Then we can assert lots of things about the form by using one of the following assertions in the `AssertForm` instance.

* hasMethod()
* hasAction()
* hasCSRF()
* hasSpoofMethod()
* has()
* hasEnctype()
* containsInput()
* containsTextArea()
* contains()
* containsButton()
* doesntContain()

Let's take a look at each of these assertions one by one.

The `hasMethod()` assertion checks to see if the form has the given method.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST');
    });
```

The `hasMethod()` method normalizes the string passed into it, so both `POST` and `post` will match.
That means that you don't have to worry if you use upper or lower case in your view.

The `hasAction()` method check that your form has the given action.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST')
            ->hasAction('/records/store');
    });
```

The `hasCSRF()` method doesn't need any parameters, and it checks that you have the hidden token field that protects
you from cross site request forgeries.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST')
            ->hasAction('/records/store')
            ->hasCSRF();
    });
```

Since the support for all the methods are a bit sketchy at best, Laravel uses method spoofs for the form methods
other than the `POST` method, and you can assert for the existence of the desired method spoof by using 
`hasSpoofMethod()`. The `hasSpoofMethod()` takes one argument, which is a string containing the method you want to spoof.

So if we want to make sure that our update form has the `PUT` method spoof we can then do this.

```php
get('/records/edit')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST')
            ->hasAction('/records/update')
            ->hasCSRF()
            ->hasSpoofMethod('PUT');
    });
```

You can also do it like this, but I think that the readability of the test suffers.

```php
get('/records/edit')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('PUT')
            ->hasAction('/records/update')
            ->hasCSRF();
    });
```

If you pass any other method than `GET` or `POST` to the `hasMethod()` it will assume that you want to check
for the spoof instead. I'm not sure that I like that and I might just pass a PR for that at the GitHub repo.

Just like with the element assertions you can check for any kind of attributes using the `has()` method.
If you want you can use Laravel's magic methods so instead of typing `has('enctype', 'multipart/form-data')`,
you can use `hasEnctype` where pass you pass the `enctype` as a parameter like so `hasEnctype('multipart/form-data')`.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST')
            ->hasAction('/records/store')
            ->hasCSRF()
            ->hasEnctype('multipart/form-data');
    });
```

Input fields and text areas are very easy to test for, just use the `contains` method on the `AssertForm` instance,
and to make it very readable you use those magic methods again like so.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->hasMethod('POST')
            ->hasAction('/records/store')
            ->hasCSRF()
            ->hasEnctype('multipart/form-data');
            ->containsInput([
                'name' => 'title',
                'value' => 'Some title'
            ])
            ->containsTextarea([
               'name' => 'comments',
                'value' => 'The text you want to assert'
            ]);
    });
```

You can use magic methods to test for other types of inputs, labels, and buttons, or you can use the longer form. 

```php
$form->contains('label', [
    'for' => 'title'
]);

$form->containsLabel([
    'for' => 'title'
]);
```

You can also make sure that a form doesn't contain a certain element by using the `doesntContain()` method.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->doesntContain('label', [
        'for' => 'password'
        ]);
    });
```

You can use the magic methods on the `doesntContain` assertions as well, just like the `contains` assertions.

Select has warranted its own assertion class, called `AssertSelect`. The `AssertSelect` API differs a bit from 
the other assertion classes we have been using so far, as we need to use the `findSelect()` method to find the
select we are looking for. If we have more than one select we can use the `nth-of-type()` selector, and if we 
only have one we don't need to pass any selector, just the closure that we need for making our assertions on the
select.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->findSelect('select:nth-of-type(2)', function (AssertSelect $select) {
            $select->has('name', 'country');
        });
    });
```

You can also assert that it contains options, by using the `->containOption()` or the `->containsOptions()` methods.
The first takes a single array of keys and values like this.

```php
$select->containsOption([
    'value' => 1,
    'text' => 'Sweden'    
]);
```

You can also check for properties like `x-data` if you want too.

The `->containsOptions()` method is for checking that you have a bunch of options in your select.

```php
$select->containsOptions(
    [
        'value' => 'dk',
        'text' => 'Denmark'
    ],
    [
        'value' => 'se',
        'text' => 'Sweden'
    ]);
```

You can check if a value is selected with the `->hasValue()` method, or check multiple values with the `->hasValues()`
method.

```php
$select->hasValue('se');

$select->hasValues(['dk', 'se']);
```

The `->hasValue()` and `->hasValues` are syntactic sugar for.

```php
$select->containsOption([
    'selected' => 'selected',
    'value' => 'dk'
]);

$select->containsOptions(
[
    'selected' => 'selected',
    'value' => 'dk'
],
[
    'selected' => 'selected',
    'value' => 'se'
]
);
```

Datalists are very similar to selects, the difference is that they are hidden, and that they are referenced in a
property on the input text item. This is how you assert that a form has a datalist and that it contains the values
you are looking for.

```php
get('/records/create')
    ->assertOk()
    ->assertFormExists('#my-form', function (AssertForm $form) {
        $form->findDatalist('#my-datalist', function (AssertDatalist $datalist) {
            $datalist->containsOption(['value' => 'Sweden']);
        });
    });
```

And for multiple values you use the `->containsOptions()` assertion. 

```php
        $form->findDatalist('#my-datalist', function (AssertDatalist $datalist) {
            $datalist->containsOptions(
            [
                'value' => 'Denmark'
            ],
            [
                'value' => 'Sweden'
            ],
            );
        });
```

So now we have looked at how to do each step, let's try applying this to a real life example.
We will be looking at the `books.edit` view of my pet project Mediabase, and try to write tests for it
using this package. Now I already have a test suite for this, so I will not TDD the form from scratch.

Let's do it piece by piece, starting with making sure that the view has a form, and since it only has one
form we don't need to pass any additional parameters.

```php
it('has a form', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists();
});
```

We then check for the proper method on the form.

```php
it('has a form with a post method', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->hasMethod('post');
        });
});
```

Then we look for the correct action.

```php
it('has a form with the correct action', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->hasAction(route('books.update', $this->book));
        });
});
```

The proper spoof method for an update.

```php
it('has a spoof method of put', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->hasSpoofMethod('put');
        });
});
```

The CSRF field.

```php
it('has a CSRF token field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form){
           $form->hasCSRF();
        });
});
```

Now we are five tests into our form, and so far we have only tested that the form has the proper method, action, 
method spoof, and that it has the CSRF token. I believe we can make all these tests into a single test.

```php
it('has an update form with the necessary parts needed by laravel', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->hasMethod('post')
                ->hasAction(route('books.update', $this->book))
                ->hasSpoofMethod('put')
                ->hasCSRF();
        });
});
```

Now isn't that much better?
Hey, shouldn't we test the form's enctype? Well since we don't send any files with the form there's no need for it.

We could continue chaining on the test above, but I think it's a good idea to test each input on its own.

Let's start with the `title` field. We assert that we have a label for our `title` input, and that we have the
`name`, `id` and `value` of the input. The reason for asserting that the input has the correct `id` is that the
`for` attribute in the label needs a corresponding `id` on the input.  

```php
it('contains a title field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'title'
            ])
            ->containsInput([
               'name' => 'title',
               'id' => 'title',
               'value' => $this->book->title
            ]);
        });
});
```

We do the same thing for the published year field.

```php
it('contains a published_year field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'published_year'
            ])
                ->containsInput([
                    'name' => 'published_year',
                    'id' => 'published_year',
                    'value' => $this->book->published_year
                ]);
        });
});
```

The author field is a bit special, and as you can see we are using the array syntax for the field name, this is so
that we can pass multiple authors to each book.

```php
it('contains an author field', function () {
    $this->book->authors()->attach(Author::factory()->create());
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'author'
            ])
                ->containsInput([
                    'name' => 'author[]',
                    'id' => 'author',
                    'list' => 'authors',
                    'value' => $this->book->authors[0]->last_name . ', ' . $this->book->authors[0]->first_name
                ])
                ->containsDatalist([
                    'id' => 'authors'
            ]);
        });
});
```

There are two thing that we need to pay attention to here as well. The first is that we use the `list` attribute,
and we give it the value of the id on the datalist. The second thing is that we check for a datalist element with
the id `authors`. We do not check that the datalist has any options here, we will do that in a later test. 

Next up is the format field, and that just like the authors field uses a datalist. The same goes for the genre
field.

```php
it('contains a format field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'format'
            ])
                ->containsInput([
                    'name' => 'format_name',
                    'id' => 'format',
                    'list' => 'formats',
                    'value' => $this->book->format->name
                ])
                ->containsDatalist([
                    'id' => 'formats'
                ]);
        });
});

it('contains a genre field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'genre'
            ])
                ->containsInput([
                    'name' => 'genre_name',
                    'id' => 'genre',
                    'list' => 'genres',
                    'value' => $this->book->genre->name
                ])
                ->containsDatalist([
                    'id' => 'genres'
                ]);
        });
});
```

The following to test makes sure that we have an ISBN field and a blurb textarea.

```php
it('contains an isbn field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'isbn'
            ])
                ->containsInput([
                    'name' => 'isbn',
                    'id' => 'isbn',
                    'value' => $this->book->isbn
                ]);
        });
});

it('contains a blurb text area', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'blurb'
            ])
                ->containsTextarea([
                    'name' => 'blurb',
                    'id' => 'blurb',
                    'value' => $this->book->blurb,
                ]);
        });
});
```

The thing to notice is that the content of the textarea needs to be handled with the value attribute. 
A textarea doesn't really have a `value` attribute, as you can see here 
[MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/textarea#attributes). 
It works with the value or the `text` attribute since the package normalizes it behind the scenes. 

There isn't much to say about the series field, it is another input that uses a datalist.

```php
it('contains a series field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'series'
            ])
                ->containsInput([
                    'name' => 'series_name',
                    'id' => 'series',
                    'list' => 'series-list',
                    'value' => $this->book->series->name
                ])
                ->containsDatalist([
                    'id' => 'series-list'
                ]);
        });
});
```

We will not take any closer look on the `part` and `publisher` fields, since they are more of the same that we already 
looked at.

```php
it('contains a part field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'part'
            ])
                ->containsInput([
                    'name' => 'part',
                    'id' => 'part',
                    'value' => $this->book->part
                ]);
        });
});

it('contains a publishers field', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'publisher'
            ])
                ->containsInput([
                    'name' => 'publisher_name',
                    'id' => 'publisher',
                    'list' => 'publishers',
                    'value' => $this->book->publisher->name
                ])
                ->containsDatalist([
                    'id' => 'publishers'
                ]);
        });
});
```

The next test is a bit special, they both are and aren't a part of the form. They are meant to be
used for adding and removing author fields. Maybe I shouldn't have put them inside the form tag, but that is what I did.
I will later on add some JavaScript to handle the necessary DOM updates needed when the buttons are clicked.

```php
it('contains buttons for adding and removing author inputs', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
           $form->containsButton([
               'title' => 'Add Author',
           ])
           ->containsButton([
              'title' => 'Remove Author',
           ]);
        });
});
```

I just check that they are buttons and that they have the correct titles. If you don't know what the global title
attribute does, it's the little balloon tip that pops up when you are hovering over the element. 
You can read more about those here [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/title).

For the submit button I just make sure that there is an input of the type submit.

```php
it('contains a submit button', function () {
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->containsInput([
                'type' => 'submit',
            ]);
        });
});
```

Submit buttons are a bit strange since you can create them in two ways.

Like this

```html
<input type="submit" value="Submit">
```

Or like this

```html
<button type="submit">Submit</button>
```

That means that you need to write your assertions accordingly. For the first way you can use the `->containsInput()`
like I did, and for the second you can use the `->containsButton()` assertion.

You remember that I had a test that checks that we have the author field, well this one checks and makes sure that
we have one for each author, and remember an id is only allowed once on a valid html page. That means that the second
author field should not contain any `id="author"` attribute. To make sure that we only have one input with the id
of `author` we add another `->containsInput()` but this time, as a second argument we pass a number, and that number
makes sure that we only have one input on that form with the id of `author`.

```php
it('contains an author field for each author', function () {
    $this->book->authors()->attach(Author::factory()->create());
    $this->book->authors()->attach(Author::factory()->create());
    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function(AssertForm $form) {
            $form->containsLabel([
                'for' => 'author'
            ])
            ->containsInput([
                'id' => 'author'   
            ], 1)      
            ->containsInput([
                'name' => 'author[]',
                'id' => 'author',
                'list' => 'authors',
                'value' => $this->book->authors[0]->last_name . ', ' . $this->book->authors[0]->first_name
            ])
            ->containsInput([
                'name' => 'author[]',
                'list' => 'authors',
                'value' => $this->book->authors[1]->last_name . ', ' . $this->book->authors[1]->first_name
            ])
            ->containsDatalist([
                'id' => 'authors'
            ]);
        });
});
```

Now it's time to assert those datalists, this was introduced in version 1.1.1, so make sure that you have updated your
project dependencies before attempting this.

Like I said earlier, the datalist is basically a hidden select, so the syntax for testing against it is almost identical.
The most important thing about datalists is that they require an id, that means that you can't use another selector to
find it. There isn't really much more to say about datalist in the sense of asserting that they exist and that they
contain the proper values. If you want to know more about datalists, and how you can use them, you can do that here
[Using a datalist instead of a dropdown in your forms](https://tray2.se/posts/using-a-datalist-instead-of-a-dropdown-in-your-forms)

So here are the tests for the datalist for.

* Authors
* Formats
* Genres
* Series
* Publishers

```php
it('contains a list of authors', function () {
    Author::factory()
        ->count(2)
        ->sequence(
            [
                'first_name' => 'David',
                'last_name' => 'Eddings'
            ],
            [
                'first_name' => 'Terry',
                'last_name' => 'Goodkind'
            ])->create();

    get(route('books.edit', $this->book))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->findDatalist('#authors', function (AssertDataList $datalist) {
                $datalist->containsOptions(
                    ['value' => 'Eddings, David'],
                    ['value' => 'Goodkind, Terry']
                );
           });
        });
});

it('contains a list of formats', function () {
    Format::factory()
        ->count(2)
        ->sequence(
            [
                'name' => 'Pocket',
                'media_type_id' => $this->mediaTypeId,
            ],
            [
                'name' => 'Hardcover',
                'media_type_id' => $this->mediaTypeId,
            ]
        )
        ->create();

    get(route('books.create'))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->findDatalist('#formats', function (AssertDataList $datalist) {
                $datalist->containsOptions(
                    ['value' => 'Hardcover'],
                    ['value' => 'Pocket']
                );
            });
        });
});

it('contains a list of genres', function () {
    Genre::factory()
        ->count(2)
        ->sequence(
            [
                'name' => 'Fantasy',
                'media_type_id' => $this->mediaTypeId,
            ],
            [
                'name' => 'Crime',
                'media_type_id' => $this->mediaTypeId,
            ]
        )
        ->create();

    get(route('books.create'))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->findDatalist('#genres', function (AssertDataList $datalist) {
                $datalist->containsOptions(
                    ['value' => 'Crime'],
                    ['value' => 'Fantasy']
                );
            });
        });
});

it('contains a list of series', function () {
    Series::factory()
        ->count(2)
        ->sequence(
            ['name' => 'The Wheel Of Time'],
            ['name' => 'The Sword Of Truth']
        )
        ->create();

    get(route('books.create'))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->findDatalist('#series-list', function (AssertDataList $datalist) {
                $datalist->containsOptions(
                    ['value' => 'The Sword Of Truth'],
                    ['value' => 'The Wheel Of Time']
                );
            });
        });
});

it('contains a list of publishers', function () {
    Publisher::factory()
        ->count(2)
        ->sequence(
            ['name' => 'TOR'],
            ['name' => 'Ace Books']
        )
        ->create();

    get(route('books.create'))
        ->assertOk()
        ->assertFormExists(function (AssertForm $form) {
            $form->findDatalist('#publishers', function (AssertDataList $datalist) {
                $datalist->containsOptions(
                    ['value' => 'Ace Books'],
                    ['value' => 'TOR']
                );
            });
        });
});
```

That is it for now, just a quick side note before I leave you to do whatever it is you do when not reading my posts. 
The package works out of the box with Laravel Livewire and since I don't know Livewire yet, I will just steal the example
from the docs.

> As livewire uses the TestResponse class from laravel, you can easily use this package with 
> Livewire without any changes

```php
Livewire::test(UserForm::class)
    ->assertElementExists('form', function (AssertElement $form) {
        $form->find('#submit', function (AssertElement $assert) {
            $assert->is('button');
            $assert->has('text', 'Submit');
        })->contains('[wire\:model="name"]', 1);
    });
```

For more information about Laravel-dom-assertions make sure to visit the repo on 
[GitHub](https://github.com/sinnbeck/laravel-dom-assertions), and don't forget to give it a star.


Are you still here reading? 

Good, because there are a couple yet undocumented assertions that you can do, and as a bonus I will include them here.

Most of you probably know that you need to have a doctype to make sure your page doesn't get render in some strange
compatibility mode by your browser. That is why it makes sense to assert that the document has the proper html5 doctype.

```php
it('has a html5 doctype ', function () {
    $this->get(route('books.index'))
        ->assertHtml5();
});
```

You can also assert that you have the proper tags in the document `head`.

```php
->assertElementExists('head', function (AssertElement $assert) {
    $assert->is('head');
  });
});
```

```php
->assertElementExists('head', function (AssertElement $assert) {
    $assert->contains('meta', [
        'charset' => 'UTF-8',
    ]);
});
```

```php
->assertElementExists('head', function (AssertElement $assert) {
    $assert->find('title', function (AssertElement $element) {
        $element->has('text', 'Nesting');
    });
});
```

Hope you will have an easier time of testing those views now.

//Tray2
