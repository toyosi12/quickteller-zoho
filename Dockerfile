FROM php:7.4-apache

RUN a2enmod rewrite

RUN a2enmod headers

RUN a2enmod ssl

RUN apt-get update && apt-get upgrade -y

COPY . /var/www/html

EXPOSE 443 

EXPOSE 80