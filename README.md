# input-formatter

A php package for formatting/transforming values.



# Intro

It is very likely you have hit a scenario where you needed to convert some values into some format or some valid value.
Sure you could achieve this and call built in php functions your self, maybe one or two functions call not so bad, but imagine
if you needed to apply several functions to your value or values? Here's an example some people might hit:

```php
//user input for a phone number
$value = '(123)348-3847   ';


//however your application doesnt care about formatted numbers such as parenthesis.

//so we do some minor cleanup on our input:
$value = trim($value);
//then strip all non numeric numbers
$value= preg_replace('/[^0-9]/', '', $value);

//then any other formats/function calls your value might need

```

Yeah maybe not the most complex example, but consider an example where you had to processes your value through 5 other function calls
to get it in a format you need. Or even worse if you had to do it on an array of input.

This is where this package comes in handy to conveniently process multiple calls for you and help keep your code cleaner.

Heres that same example using three different ways of creating a Formatter:
```php
//instance
$value = new Formatter('(123)348-3847   ')->trim()->onlyNumbers()->get();
//statically
$value = Formatter::create('(123)348-3847   ')->trim()->onlyNumbers()->get();

//helper function
$value = formatter('(123)348-3847   ')->trim()->onlyNumbers()->get();

```

With the Formatter class's ability to method chain, you can keep things less cluttered and not worry about having to make a mess of your code.


# Multiple/Array Values

The formatter class goes beyond single values and can be used on array values as well. The formatter class will apply formatter methods recursively
making it easy to convert multiple values without having to loop through them yourself:


```php

$values = formatter(['value123', 'value321', 'foo'])->onlyNumbers()->suffix('Yay!')->get();

//['123Yay!', '321Yay!', 'fooYay!']
$values = $values->get();

```

This is also the case on nested array values as well. If the value is an array, each of its values will have the formatter methods applied!


# Mass Conversions

One of the most useful features of this package is to be able to convert input a clean Laravel validation like syntax.If you are familiar with Laravel, then this should feel very natural to you:


```php

//using our \CollabCorp\Formatter\Concerns\FormatsInput trait
$data = [
    'foo'=>'bar',
    'test'=>'something%%%%%%',
    'phone_number'=>'This is my phone number (123)134-4444'
    'some_input'=>'some value',
    'some_number'=>'2'
    'some_other_input'=>'foooobar'
    //etc.
];

//can achieve the same with Formatter::convert($data,$formatterMethods);
$convertedData = $this->convert($data, [
    'foo'=>'prefix:foo',//pass args to formatter methods using this format method:commaDelimetedArgs
    'test'=>'rtrim:%',
    'number*'=>'add:2|multiply:2'
    'phone_number'=>'trim|onlyNumbers|phone' //can call multiple formatters using a pipe delimeter
]);


```

Neat huh? Wait theres more! You can also take advantage of string patterns when defining your formatter methods:

```php

$this->convert($data,[
    '*phone*'=>'....' // only call formatters if its name contains the word "phone"
    '*phone'=>'....' // only call formatters if its name ends the word "phone"
    'phone*'=>'....' // only call formatters if its name starts the word "phone"
])

```

### Closure and Objects

Need more control in your mass conversions? No problem. You can take advantage of using closures and class objects that implement our
`\CollabCorp\Formatter\Contracts\Formattable` interface:

```php
$this->convert($data,[
    'something'=>'...',
    'other_input'=>['trim',function($value){
        $value = 'new value';
        return $value;
    }],
    'something_else'=>['trim',FormattInput::class]
])

//FormatInput class:

class FormatInput implements \CollabCorp\Formatter\Contracts\Formattable{

    public function format($value)
    {
        $value = 'new value';
        return $value;
    }
}

```
This allows you to have more control over the value that is being formatted.

# Carbon Date Support
You may also convienently use the carbon library during these mass conversions:

```php
$this->convert($data,[
    //every method after toCarbon is passed to the underlying carbon instance
    //so you can call any available method on the carbon instance as needed.
   'some_data'=>'toCarbon|setTimezone:America/Indiana/Indianapolis'
]);

```

# Available Formatter Methods:

Please refer to the class/file `src/Formatter/Formatter.php` to see what available conversion methods are available.

We could list them all here but that would make this readme file feel endless and would eventually become bloated with uneccessary docs.

If extending this class or adding methods. It is important to know that Formatter class calls protected methods dynamically using `__call`

so if you wanted to add a new formatter method to the class and keep the method chain support and mass conversions from breaking, set up your

functions this way:

```

protected function somethingNew($value, $anyOtherArgs)
{
    //do stuff to value then return
    return $value;
}

```

# Kudos/Credits

The `\CollabCorp\FormatterMethodParser\FormatterMethodParser` class that processes all methods in mass conversions
utilizes several methods that were taken from classes in the `Illuminate\Support` pacakge. These are authored and credited to Laravel team. We do not take credit for writing these methods, We simply implemented them in our own class to conviniently parse methods. See the Laravel source for more info:

See <a target="_blank" href="https://github.com/laravel/laravel">Laravel Repo</a>.

## Contribute

Contributions are always welcome in the following manner:
- Issue Tracker
- Pull Requests
- Collab Corp Slack(Will send invite)


# License


The project is licensed under the MIT license.
