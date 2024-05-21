---
title: Using a datalist instead of a dropdown in your forms 
slug: using-a-datalist-instead-of-a-dropdown-in-your-forms
created_at: 2022-09-30
updated_at: 2022-09-30
published_at: 2022-09-30
author: Tray2
summary: Have you ever had a huge list of values that the users needs to scroll through to get to the item they wanted, you know the classic `Country` dropdown, and for me who lives in Sweden will have to scroll through a load of countries to get to the desired one. 
---

Have you ever had a huge list of values that the users needs to scroll through to get to the
item they wanted, you know the classic `Country` dropdown, and for me who lives in Sweden 
will have to scroll through a load of countries to get to the desired one. I know that some sites
have some really nifty JavaScript that allows you to filter the list. While this is all well and good
there is another option, we could use a datalist for this. The datalist has  built in filtering so
no more tricky JavaScript to handle that, the only caveat is that the datalist doesn't have value
and text, it only has value, let me demonstrate.

Regular dropdown.

```html
<select name="country">
    <option value="SE">Sweden</option>
</select>
```

Datalist.

```html
<input list="countries" name="country">
<datalist id="countries">
    <option value="Sweden"></option>
</datalist>
```

This means that we need to do some trickery behind the scenes to get the `SE` or the `country_id`
from the database. I personally think that it is a fair trade-off, and we have some other nifty
things as well when it comes to displaying the selected/filtered value. 

**In this demonstration I will be using Laravel.**

So let's run with the countries example, and just like we would when we create a regular dropdown,
we need to pass the values from our controller to the view.

```php
public function create()
{
    return view('address.create')
        ->with([
        'countries' => Country::query()
                        ->orderBy('name')
                        ->get(),
    ]);
}
```

In our view we create the datalist. Notice that I give the input field the name `country_name`, and not `country_id`
like I would have if it were a regular dropdown, this is so that we can make the distinction later.

```php
<input list="countries" name="country_name" id="country_name">
<datalist id="countries">
    @foreach($countries as $country)
        <option value="{{$country}}">
    @endforeach
</datalist>
```

Next up we need to create the store method, and a method that converts the country name to the country id.
I will call this method `getCountryId`, and we pass the country name from the request.

```php
public function store(Request $request)
{
    $validAddress = $request->validate([
        //The other validation rules
        'country_name' => 'required',
    ]);
    
    Address::create(array_merge($validAddress,[
        'country_id' => $this->getCountryId($request->country_name),
    ]);
}

protected function getCountryId($countryName)
{
    return Country::query()
        ->where('name', $countryName)
        ->value('id');
}
```

One thing that is very important is to set the `fillable` array on the model, you can't just set the `guarded` property
to an empty array, the reason for that is, that we validate `country_name` which we don't have in the `addresses` table.
That means that if we try to run the code above we will get an error stating that we don't have a column with the name
`country_name`. So we need to set the fillable property in the `Address` model.

```php
protected $fillable = [
  //All columns in our addresses table except id and timestamps.  
];
```

**Caution!** Unlike the dropdown, the datalist allows you to add things that doesn't exist in the list, so you need to
handle that in your validation, or create the item when it doesn't exist. We can do that by updating the `getCountryId()` 
to create it if it does not exist, or we can add a validation to the `country_name` like this.

```php
public function rules()
{
    return ([
        //The other validation rules
        'country_name' => 'required|exists:countries,name',
    ]);
}
```

If you decide that you want to create items that doesn't exist, you can just change the query 
that returns the id. Like this.

```php
//Method still in the controller
protected function getCountryId($countryName)
{
    return Country::query()
        ->firstOrCreate(['name' => $countryName])
        ->id;
}


//Method extracted to the AddressFormRequest
public function getCountryId()
{
    return Country::query()
        ->firstOrCreate(['name' => $this->country_name])
        ->id;
}
```

It's a bit debatable if a `FormRequest` really should create data, but I haven't really found
a better place for it. 


Now when the easy part is done, we need to talk about a better place to store the `getCountryId()` method.
Sure we can keep it in the controller, but we will need it in the update controller as well. Since the validation
should be handled by a form request and not inline as we did, we can move it into the form request like so.

```php
//AddressController
public function store(AddressFormRequest $request)
{
    $validAddress = $request->validated
    Address::create(array_merge($validAddress,[
      'country_id' => $request->getCountryId(),
    ]);
    //Do redirect here
}

//AddressFormRequest
public function rules()
{
    return ([
        //The other validation rules
        'country_name' => 'required',
    ]);
}

public function getCountryId()
{
    return Country::query()
        ->where('name', $this->country_name)
        ->value('id');
}
```

This way you can use the `getCountryId` method in both your store and update methods. Of course, you don't need to
move it to the form request if you have the `store` and `update` methods in the same controller, in other words you
only need to do this if you are using single action controllers.

So how do we handle this in our edit view?
We handle it like we would any other input, and we don't need to loop over the options in the country field to determine
which one was selected before, we just assign the value to it.

So let's start with our controller.

```php
public function edit(Address $address)
{
    return view('address.edit')
        ->with([
        'address' => $address->load('country'),
        'countries' => Country::query()
            ->orderBy('name')
            ->get(),
    ]);
}
```

We need to load the country relation since we don't have the id from the country table to compare with, and we also
need to fetch all the countries so that we can populate the datalist. I think me showing this step shouldn't be 
necessary, but I will do that anyway just in case. We need to define the relationship that `Address` has with `Country`
in our `Address` model.

```php
public function country()
{
    return $this->belongsTo(Country::class);
}
```

Our edit form will look almost identical to the one we used in the create view.

```php
<input list="countries" name="country_name" id="country_name" value="{{ $address->country->name }}">
<datalist id="countries">
    @foreach($countries as $country)
        <option value="{{$country}}">
    @endforeach
</datalist>
```

The only difference is that we assign the value for the `country_name` input.

So we are almost there, we just need to handle the update and some validation issues. Let's start with the update.

```php
public function store(Address $address, AddressFormRequest $request)
{
    $validAddress = $request->validated
    $address->update(array_merge($validAddress,[
      'country_id' => $request->getCountryId(),
    ]);
    // Do redirect here.
}
```

Nothing really out of the ordinary going on here, except that we once again fetch the `country_id` from the database.
If you stuck with a regular, multi-action controller and kept the `getCountryId` inside the controller, just change
this line.

```php
'country_id' => $request->getCountryId(),
```

To this and you should be good to go. 

```php
'country_id' => $this->getCountryId($request->country_name),
```

So what about validation failures?
We just use the `old` helper like we would any other field.

In our `create` form we just do this.

```php
<input list="countries" name="country_name" id="country_name" value="{{ old('country_name') }}">
```

We add the value just like we would on a regular text input field, and we do the same for the `edit` form.

```php
<input list="countries" name="country_name" id="country_name" value="{{ old('country_name', $address->country->name) }}">
```

The only difference is that we add the value received from the database as the second parameter to the `old` helper.

I hope you have enjoyed this quick post on something other than database related things, and I thank you for reading
this post. I would also like to thank my friend [@rsinnbeck](https://twitter.com/@rsinnbeck) for helping with the
editing and the technical review. He also has a very good [blog](https://sinbeck.dev) that you should check out.

//Tray2


