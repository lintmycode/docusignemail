
# Use PHP 7.0 official image as the base image
FROM php:7.0-cli

# Replace with archived sources
RUN echo "deb http://archive.debian.org/debian/ stretch main contrib non-free" > /etc/apt/sources.list \
    && echo "deb-src http://archive.debian.org/debian/ stretch main contrib non-free" >> /etc/apt/sources.list \
    && echo "deb http://archive.debian.org/debian-security/ stretch/updates main" >> /etc/apt/sources.list \
    && echo "deb-src http://archive.debian.org/debian-security/ stretch/updates main" >> /etc/apt/sources.list

# Disable SSL verification for APT to avoid expired SSL certificates for archived repositories
RUN apt-get -o Acquire::Check-Valid-Until=false update

# Install system dependencies for Composer
RUN apt-get install -y git unzip

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /app

# Copy the application code to the working directory
COPY . /app

# Command to run the PHP built-in server
CMD ["php", "-S", "0.0.0.0:80"]
