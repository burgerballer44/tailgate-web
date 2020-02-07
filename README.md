# Tailgate Web

## Composer

Install the dependencies by running `composer install`. Run `composer install --no-dev` for production environments.

## Environment

Create a .env file by copying and the .env.example file. Update the values accordingly in the new .env file. The client_id and client_secret should be from the API client configuration.

## NPM

To install the node packages run `npm install`.  
To create an initial css file run `npm run dev:css`.  
To build the final minified css file run `npm run build:css`.  

## Deployment

`var` directory needs to be writable.  
Compile css with `npm run build:css`.  
Remove container cache file at `var/cache/container`.  