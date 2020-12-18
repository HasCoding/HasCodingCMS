
# HasCodingCMS
Simple and Open Source PHP Frameworks 

# What is HasCodingCMS
HasCodingCMS is an Application Development Framework - a toolkit - for people who build web sites using PHP.
Its goal is to enable you to develop projects much faster than you could if you were writing code from scratch,
by providing a rich set of libraries for commonly needed tasks, as well as a simple interface and logical structure to access these libraries.
HasCodingCMS lets you creatively focus on your project by minimizing the amount of code needed for a given task.

#Server Requirements
PHP version 5.6 or newer is recommended.

It should work on 5.4.8 as well, but we strongly advise you NOT to run such old versions of PHP,
because of potential security and performance issues, as well as missing features.

#Installation

Open the zip file to the directory where you will be installing then, in the /System/Config/config.php file.
Change `$site_url`, `$site_session_name` variables to your own.

sonraki ayarlar genel framework yapısı 

System/Controller/  <- back-end kodlarınızın olduğu klasör
System/Model/ <- veritabanı dosyalarınızın olduğu klasör
System/View  <- front-end kodlarınızın olduğu klasör

#Creating a Simple Controller

First create a php file under the System/Controller folder.
For example: Let's create a file called `Hasan`.
The content of the file should be as follows.

```php
<?php
class Hasan extends Has_Controller
{

     public function index ()
     {
         echo "Controller has been created successfully.";
     }

}
?>
``` 
here, make sure the class name is the same as the filename.

To your controller

You can access it as https:// `$site_url`/controllername.








