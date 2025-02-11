# MY_Parser Class for CodeIgniter 3

This is a custom parser class for CodeIgniter 3, extending the built-in parser to provide additional features such as custom filters, block support, includes, extends, caching, and dynamic data handling.

## Features

- Custom Filters (uppercase, lowercase, trim, etc.)
- Block Support for reusable template blocks
- Includes for loading partial templates
- Extends for template inheritance
- Caching to improve performance
- Dynamic data handling
- Security enhancements to sanitize input data

## Installation

1. **Download and Extract**: Download the ZIP file from the repository and extract it to your CodeIgniter project's libraries folder.

2. **Load the Library**: Load the `MY_Parser` library in your CodeIgniter application.

```php
$this->load->library('parser', 'MY_Parser');
-Also you can load
$this->load->library('parser', 'my_parser');
```

## Usage 

Set Data
Use the set_data method to set dynamic data that can be used across multiple templates.

```php
$this->my_parser->set_data([
    'site_name' => 'My Awesome Site',
    'year' => date('Y')
]);
```

Assign Variables
Use the assign method to assign individual variables.

```php
$this->my_parser->assign('author', 'Shivasis Biswal');
```

Parse Template
Use the parse method to parse a template with the assigned data.

```php
$data = [
    'title' => 'Welcome to My Site',
    'created_at' => '2025-11-02 09:59:00'
];
$this->my_parser->parse('template_name', $data);
```

Custom Filters 

Define and use custom filters in your templates. 

Example usage:

```{created_at|date:d-m-Y}```

Upper

Description: Converts text to uppercase. 

Template Example: ```{title|upper}``` 

Output: HELLO WORLD

Lower 

Description: Converts text to lowercase. 

Template Example: ```{title|lower}``` 

Output: hello world

Capitalize
Description: Capitalizes the first letter of each word. 
Template Example: ```{title|capitalize}``` 
Output: Hello World

Trim
Description: Trims whitespace from both ends. Template Example: ```{title|trim}``` Output: Hello World

Length
Description: Returns the length of the string. Template Example: ```{title|length}``` Output: 11

Reverse
Description: Reverses the string. Template Example: ```{title|reverse}``` Output: dlroW olleH

Md5
Description: Returns the MD5 hash of the string. Template Example: ```{title|md5}``` Output: fc3ff98e8c6a0d3087d515c0473f8677

nl2br
Description: Converts newlines to ```<br>``` tags. Template Example: ```{description|nl2br}``` Output: Hello```<br>```World

Esc
Description: Escapes HTML special characters. Template Example: ```{html_content|esc}``` Output: ```&lt;div&gt;Hello&lt;/div&gt;```

Absolute
Description: Converts to absolute value. Template Example: ```{number|absolute}``` Output: 42 (if number = -42)

Round
Description: Rounds the number. Template Example: ```{number|round}``` Output: 3 (if number = 3.1415)

Date
Description: Formats date according to the specified format. Template Example: ```{created_at|date:d-m-Y}``` Output: 02-11-2025

Limit Chars
Description: Limits the string to a certain number of characters. Template Example: ```{title|limit_chars:5}``` Output: Hello (if title = Hello World)

Limit Words
Description: Limits the string to a certain number of words. Template Example: ```{title|limit_words:1}``` Output: Hello (if title = Hello World)

Highlight
Description: Highlights the specified term. Template Example: ```{content|highlight:Hello}``` Output: <strong>Hello</strong> World

Strip Tags
Description: Strips HTML tags. Template Example: ```{html_content|strip_tags}``` Output: Hello (if ```<div>Hello</div>```)

Example Template
```html
<!-- template_name.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{title}</title>
</head>
<body>
    <h1>{site_name}</h1>
    <p>Created Date: {created_at|date:d-m-Y}</p>
    <p>Author: {author}</p>
</body>
</html>
```

#### Block Support
Define and use blocks within your templates:
```html
{% block content %}
<p>This is a block of content.</p>
{% endblock %}
```

#### Includes and Extends
Include partial templates or extend base templates:

```html
{% include:header %}
{% extend:base %}
```

Example for Include:

Assume you have a common header file header.html:
```html
<!-- header.html -->
<header>
    <h1>Welcome to My Website</h1>
</header>
```

And a main template file index.html:
```html
<!-- index.html -->
{% include:header.html %}
<main>
    <p>This is the main content of the page.</p>
</main>
```

When index.html is parsed, it will include the content of header.html, resulting in the following output:
```html
<header>
    <h1>Welcome to My Website</h1>
</header>
<main>
    <p>This is the main content of the page.</p>
</main>
```

Example for Extend:

Assume you have a base layout file base.html:
```html
<!-- base.html -->
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
</head>
<body>
    {% block content %}
    <p>This is the default content.</p>
    {% endblock %}
</body>
</html>
```

And a child template file index.html:

```html
<!-- index.html -->
{% extend:base.html %}
{% block title %}Home Page{% endblock %}
{% block content %}
<p>This is the main content of the home page.</p>
{% endblock %}
```

When index.html is parsed, it will extend base.html and replace the title and content blocks with its own content, resulting in the following output:
```html
<!DOCTYPE html>
<html>
<head>
    <title>Home Page</title>
</head>
<body>
    <p>This is the main content of the home page.</p>
</body>
</html>
```

Example Usage

With the MY_Parser class, you can now use blocks and extends in your templates:

Base Template (base.php):
```html
<!-- base.php -->
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
</head>
<body>
    {% block content %}
    <p>This is the default content.</p>
    {% endblock %}
</body>
</html>
```

Child Template (index.php):
```html
<!-- index.php -->
{% extend:base.php %}
{% block title %}Home Page{% endblock %}
{% block content %}
<p>This is the main content of the home page.</p>
{% endblock %}
```

When index.php is parsed, it will extend base.php and replace the title and content blocks with its own content, resulting in the following output:
```html
<!DOCTYPE html>
<html>
<head>
    <title>Home Page</title>
</head>
<body>
    <p>This is the main content of the home page.</p>
</body>
</html>
```


#### Loops Example
Loops allow you to iterate over arrays and display their contents dynamically. Here's how you can use loops in your templates:
```html
<!-- template_with_loop.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{title}</title>
</head>
<body>
    <h1>{site_name}</h1>
    <ul>
        {% loop items %}
        <li>{name} - {price}</li>
        {% endloop %}
    </ul>
</body>
</html>
```

Data for Loop Example

```php
$data = [
    'title' => 'Product List',
    'site_name' => 'My E-commerce Site',
    'items' => [
        ['name' => 'Product 1', 'price' => '$10'],
        ['name' => 'Product 2', 'price' => '$20'],
        ['name' => 'Product 3', 'price' => '$30']
    ]
];

$this->my_parser->parse('template_with_loop', $data);
```

Expected Output
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product List</title>
</head>
<body>
    <h1>My E-commerce Site</h1>
    <ul>
        <li>Product 1 - $10</li>
        <li>Product 2 - $20</li>
        <li>Product 3 - $30</li>
    </ul>
</body>
</html>
```

#### Conditionals Example (if, elseif)
Conditionals allow you to display content based on certain conditions. Here's how you can use conditionals in your templates:
```html
<!-- template_with_conditionals.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{title}</title>
</head>
<body>
    <h1>{site_name}</h1>
    {% if is_logged_in %}
    <p>Welcome back, {user_name}!</p>
    {% else %}
    <p>Please log in to continue.</p>
    {% endif %}
</body>
</html>
```

Data for Conditionals Example
```php
$data = [
    'title' => 'User Dashboard',
    'site_name' => 'My E-commerce Site',
    'is_logged_in' => true,
    'user_name' => 'Jane Doe'
];
$this->my_parser->parse('template_with_conditionals', $data);
```

Expected Output
When is_logged_in is true:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
</head>
<body>
    <h1>My E-commerce Site</h1>
    <p>Welcome back, Jane Doe!</p>
</body>
</html>
```

#### Caching
Enable caching to improve performance:

```php
$this->my_parser->enable_cache(TRUE);
```


#### Security
Sanitize input data to prevent injection attacks:

```php
$data = [
    'title' => 'Welcome to My Site',
    'created_at' => '2025-11-02 09:59:00'
];
$this->my_parser->sanitize_input($data);
```


## Contributing

Feel free to submit pull requests or report issues. Contributions are welcome!
License

This project is licensed under the MIT License.

Happy coding! 😊
