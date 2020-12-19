
# HasCodingCMS
Simple and Open Source PHP Frameworks 

# Download Realese Version
+ Realede version : [Download](https://github.com/HasCoding/HasCodingCMS/releases "Download HasCodingCMS")
+ Composer install : `composer require hascoding/cms dev-main`
	+ After using composer, move everything in the `/vendor/hascoding/cms` folder to the root directory of your site.




# What is HasCodingCMS
HasCodingCMS is an Application Development Framework - a toolkit - for people who build web sites using PHP.
Its goal is to enable you to develop projects much faster than you could if you were writing code from scratch,
by providing a rich set of libraries for commonly needed tasks, as well as a simple interface and logical structure to access these libraries.
HasCodingCMS lets you creatively focus on your project by minimizing the amount of code needed for a given task.

# Server Requirements
PHP version 5.6 or newer is recommended.

It should work on 5.4.8 as well, but we strongly advise you NOT to run such old versions of PHP,
because of potential security and performance issues, as well as missing features.

# Installation

Open the zip file to the directory where you will be installing then, in the /System/Config/config.php file.
Change `$site_url`, `$site_session_name` variables to your own.

next settings general framework structure

+ System/Controller/ <- folder with your back-end codes
+ System/Model/ <- folder where your database files are
+ System/View/ <- folder with your front-end codes

# URL Structure

For example for URL = http:// `$site_url`/Controller/Action/Parameters

# Creating a Simple Controller

First create a php file under the System/Controller folder.
For example: Let's create a file called `Hasan`.
The content of the file should be as follows.

**Codes written in System/Controller/Hasan.php file**
```php

<?php
class Hasan extends Has_Controller
{

     public function index() //
     {
         echo "Controller has been created successfully.";
     }
     
     public function demo($par1="",$par2="")  //demo is action , $par1 and $par2 is parameters
     {
          echo $par1;
     }
     
     public function add()
     {
          $data= [
               "data1"  = "this is data1",
               ""data2 = "this is data2"
          ]
          $this->view("Hasan/Add",$data); // Hasan/Add is System/View/Hasan/Add.php , $data is the data sent to the view file
     }
    

}
?>
``` 
here, make sure the class name is the same as the filename.

To your controller

+ for the index page :  https:// `$site_url`/Hasan
+ for the demo page :  https:// `$site_url`/Hasan/demo
+ for the add page :  https:// `$site_url`/Hasan/add


# Views
Views are used to display information (normally HTML). View files go in the `System/View` folder. Views can be in one of two formats: Standard PHP or PHTML

**Codes written in System/View/Hasan/Add.php file**
```php


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data1;?></title>
</head>
<body>
    <?php echo $data2;?>
</body>
</html>


```





