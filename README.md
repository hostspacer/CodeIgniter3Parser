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

Usage
Set Data
Use the set_data method to set dynamic data that can be used across multiple templates.
$this->my_parser->set_data([
    'site_name' => 'My Awesome Site',
    'year' => date('Y')
]);

Assign Variables
Use the assign method to assign individual variables.
$this->my_parser->assign('author', 'Shivasis Biswal');

Parse Template
Use the parse method to parse a template with the assigned data.
$data = [
    'title' => 'Welcome to My Site',
    'created_at' => '2025-11-02 09:59:00'
];

$this->my_parser->parse('template_name', $data);

Custom Filters
Define and use custom filters in your templates. Example usage:
{created_at|date:d-m-Y}

Example Template
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

Block Support
Define and use blocks within your templates:

{% block content %}
<p>This is a block of content.</p>
{% endblock %}

Includes and Extends
Include partial templates or extend base templates:

{% include:header %}
{% extend:base %}

Caching
Enable caching to improve performance:

$this->my_parser->enable_cache(TRUE);

Security
Sanitize input data to prevent injection attacks:

$data = [
    'title' => 'Welcome to My Site',
    'created_at' => '2025-11-02 09:59:00'
];
$this->my_parser->sanitize_input($data);

Contributing

Feel free to submit pull requests or report issues. Contributions are welcome!
License

This project is licensed under the MIT License.

Happy coding! 😊
