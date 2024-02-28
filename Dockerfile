
# Use PHP latest official image as the base image
FROM php:latest

# Set the working directory inside the container
WORKDIR /app

# Copy the application code to the working directory
COPY . /app

# Command to run the PHP built-in server
CMD ["php", "-S", "0.0.0.0:80"]
