## Upload images to Azure Blob Storage using PHP

How to upload images to [Azure Blob Storage](https://learn.microsoft.com/en-us/azure/storage/blobs/) using PHP?
That's the intention of this project: "Upload Images to Azure Blob Storage using PHP" . 

The approach to solve the problem is: 
1. An endpoint to upload the image.
2. A page to visualize the uploaded image.

This project consists in a small symfony app holding both of them; the endpoint and the visualization page.

### Important Note:

This project assumes you are subscribed to Azure Blob Storage service and:

1. A storage account is created and configured correctly.
2. The authentication/authorization is done by using Azure Active Directory service. 
3. The authentication by Key Access must be disabled. It's not a good practice in 
terms of security.
4. The following config values are required by this project:
    - Storage Account Name
    - Client Id 
    - TenantId 
    - Client Secret
     

#### Note: <i>It's out of scope how to set up an Azure Storage Account</i>

### Installation

    - git clone git@github.com:yjmorales/php_azure_blob_storage.git
    - cd php_azure_blob_storage
    - composer install  
    
### Virtual host

In case this example is hosted by apache, the following is a VHost configuration example you can use:

    <VirtualHost *:80>
        ServerName myazureblobstorage.com
        ServerAlias www.myazureblobstorage.com
        DirectoryIndex index.php
        ServerAdmin email@domain.com
        DocumentRoot "/var/www/html/php_azure_blob_storage/public"
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
        <Directory "/var/www/html/php_azure_blob_storage/public">
            Options Indexes FollowSymLinks MultiViews
            AllowOverride All
            Order allow,deny
            allow from all
            Require all granted
            <IfModule mod_rewrite.c>
                RewriteEngine On
                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^.*$ /index.php
            </IfModule>
        </Directory>
    </VirtualHost>

Note: The above config is just an example. 

### Update /etc/hosts file

In case apache is running on the same development station a new entry to the local
host file is needed:

{local_ip} myazureblobstorage.com

Where **local_ip** is the ip of the local server ip

### Implementation - Endpoint

- **Endpoint:** https://myazureblobstorage.com/upload
- **Method:** POST
- Payload:


    {
        "imageBase64": "<image_base_64_content>"
    }

Where `image_base_64_content` should be a valid base64 image content. 
    
- **Response:** The response is a JSON response:


    {
        'success': true|false,
        'errors': [],
        'code': int
    }


### Implementation - Page to Visualize the uploaded image

     myazureblobstorage.com/


### Contact me

Yenier Jimenez
<br>
http://yenierjimenez.com
<br>
yjmorales86@gmail.com
