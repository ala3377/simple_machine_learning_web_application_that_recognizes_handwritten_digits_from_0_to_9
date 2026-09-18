# Handwritten Digit Recognition Project Setup Guide

## 1. Project Overview

This project is a simple machine learning web application that recognizes handwritten digits from 0 to 9. The user draws a digit on a canvas in the browser, sends the image to the server, and the application predicts which digit it is using a neural network.

The project combines:

- Laravel as the backend framework
- PHP for logic and request processing
- JavaScript and Canvas for drawing interaction
- Python and NumPy for training the model
- MNIST-based data for training and evaluation

---

## 2. Project Goal

The main goal of the project is to:

- allow the user to draw a digit from 0 to 9
- convert the drawing into a normalized pixel array
- pass the data via a neural network model
- predict the most likely digit
- display the result and confidence score

---

## 3. Technologies and Programming Languages Used

### Backend
- PHP 8.1+
- Laravel 10
- Composer

### Frontend
- HTML5
- CSS
- JavaScript
- Blade templates
- Tailwind CSS
- Canvas API

### Machine Learning / Training
- Python 3
- NumPy
- MNIST dataset files

### Tooling
- Vite
- Laravel Vite plugin
- Axios
- Git / GitHub
- PHPUnit

---

## 4. Main Libraries and Packages

### Composer dependencies
- laravel/framework
- laravel/sanctum
- laravel/tinker
- guzzlehttp/guzzle

### Dev dependencies
- fakerphp/faker
- laravel/pint
- laravel/sail
- mockery/mockery
- nunomaduro/collision
- phpunit/phpunit
- spatie/laravel-ignition

### NPM dependencies
- axios
- vite
- laravel-vite-plugin

---

## 5. Project Structure

```text
handwritten_digit_recognition/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── DigitRecognitionController.php
│   └── Services/
│       └── NeuralNetwork.php
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       └── digit-recognition/
│           └── index.blade.php
├── routes/
│   └── web.php
├── storage/
│   └── app/
│       └── neural_network_model.json
├── public/
├── train_mnist_python.py
├── neural_network_model.json
├── composer.json
├── package.json
├── phpunit.xml
├── README.md
├── DOCUMENTATION.md
├── .env.example
├── .gitignore
├── artisan
├── vite.config.js
└── vendor/
```

---

## 6. Important Files

### app/Services/NeuralNetwork.php
Contains the neural network class with:
- weight and bias initialization
- sigmoid activation function
- softmax calculation
- forward propagation
- prediction logic
- model loading and saving

### app/Http/Controllers/DigitRecognitionController.php
Handles:
- the main page view
- image submission from the browser
- pixel conversion
- prediction response in JSON format
- model information and retraining endpoints

### resources/views/digit-recognition/index.blade.php
Contains the frontend UI:
- drawing canvas
- clear button
- recognize button
- confidence and probability display
- JavaScript for drawing and AJAX calls

### routes/web.php
Defines the app routes:
- home page
- recognition API
- retraining API
- model info API

### train_mnist_python.py
Trains the neural network with Python and NumPy and exports the trained model.

---

## 7. How the App Works

### Step 1: User draws a digit
The user draws a number using the mouse or touch screen.

### Step 2: Image conversion
The canvas is converted to image data and sent to the server.

### Step 3: Preprocessing
The server processes the image by:
- resizing the image
- removing empty background space
- converting to grayscale
- normalizing values
- flattening to a vector of 784 values (28 x 28 pixels)

### Step 4: Neural network prediction
The model reads the input vector and calculates activations in hidden layers and output neurons for digits 0-9.

### Step 5: Probability calculation
The output is passed through a softmax function to determine the confidence for each digit.

### Step 6: Result
The highest probability is selected as the final predicted digit.

---

## 8. Neural Network Design

The project uses a multi-layer perceptron (MLP) with:

- Input layer: 784 neurons
- Hidden layer 1: 128 neurons
- Hidden layer 2: 64 neurons
- Output layer: 10 neurons (digits 0-9)

The activation function is sigmoid, and the final decision uses softmax.

---

## 9. Requirements

Before running the project, install:

- PHP 8.1 or newer
- Composer
- Node.js and npm
- Python 3.8+
- NumPy
- Laravel dependencies

---

## 10. Installation Steps

### 1. Clone the repository

```bash
git clone <repository-url>
cd handwritten_digit_recognition
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create the environment file

```bash
cp .env.example .env
```

Then generate the Laravel app key:

```bash
php artisan key:generate
```

### 5. Run the app

```bash
php artisan serve
```

Open:

```text
http://localhost:8000
```

### 6. Run frontend assets in development mode

```bash
npm run dev
```

---

## 11. Model Training

The project includes a Python training script:

```bash
python train_mnist_python.py
```

This script:
- loads MNIST data
- trains the neural network
- exports the model to JSON
- saves the file for PHP inference

---

## 12. Notes About the Current Project

This is a lightweight educational project and not a full production-grade AI system.

It is useful for:
- learning neural networks
- learning full-stack Laravel development
- demonstrating machine learning in a web app
- building a portfolio project

---

## 13. GitHub Upload Steps

```bash
git init -b main
git add .
git commit -m "Initial project commit"
git remote add origin <your-github-repository-url>
git push -u origin main
```

---

## 14. Recommended Project Improvements

Possible future improvements:

- implement a CNN model
- use a larger dataset
- improve preprocessing
- add accuracy measurement
- add API documentation
- add tests
- add a better dashboard UI

---

## 15. Summary

This project is a practical example of combining machine learning and web development. It demonstrates how a web app can capture handwritten input, preprocess it, and classify it using a neural network model.

It is a strong project for learning purposes and for showcasing AI development skills on GitHub.
