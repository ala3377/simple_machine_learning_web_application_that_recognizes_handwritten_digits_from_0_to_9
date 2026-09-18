# Handwritten Digit Recognition Project Setup Guide

## 1. Project Overview

This project is a simple machine learning web application that recognizes handwritten digits from 0 to 9. The user draws a digit on a canvas in the browser, sends the image to the server, and the application predicts which digit it is using a neural network.

The project is designed as an educational and lightweight portfolio project. It combines:

- Laravel as the backend web framework
- PHP for the neural network logic and request handling
- JavaScript and Canvas for drawing and interaction
- Python with NumPy for model training
- MNIST-style data for training and evaluation

This is a practical example of how an artificial neural network can be used for image classification in a real web application.

---

## 2. Project Goal

The main goal of the project is to:

- allow a user to draw any digit from 0 to 9
- convert the drawing into a normalized pixel array
- pass the data to a neural network model
- predict the most likely digit
- display the predicted number and confidence score

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
- Blade templates (Laravel view engine)
- Tailwind CSS
- Canvas API for drawing digits

### Machine Learning / Training

- Python 3
- NumPy
- MNIST dataset files

### Build and Frontend Tooling

- Vite
- Laravel Vite plugin
- Axios

### Supporting Tools

- Git
- GitHub
- PHPUnit (for testing setup in Laravel)

---

## 4. Main Libraries and Packages Used

The project depends on the following important packages:

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

## 5. Core Project Structure

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
├── .env
├── .env.example
├── .gitignore
├── artisan
├── vite.config.js
└── vendor/
```

---

## 6. Important Files and Their Roles

### app/Services/NeuralNetwork.php

This is the central machine learning class.

It contains:

- neural network initialization
- hidden layers
- input and output layer sizes
- sigmoid activation function
- softmax probability calculation
- forward propagation
- prediction logic
- model saving and loading
- training logic

### app/Http/Controllers/DigitRecognitionController.php

This controller handles:

- displaying the main page
- receiving the drawn image from the browser
- converting the image to a 28x28 pixel matrix
- extracting normalized pixels
- running the model prediction
- returning JSON with the predicted digit and confidence

### resources/views/digit-recognition/index.blade.php

This is the frontend page.

It contains:

- a drawing canvas
- buttons to clear and recognize
- probability bars for each digit
- JavaScript for mouse and touch drawing
- AJAX requests to the Laravel backend API

### routes/web.php

This file defines the main app routes:

- home page
- digit recognition API endpoint
- model retraining endpoint
- model info endpoint

### train_mnist_python.py

This Python script trains the model using MNIST-like data.

It does the following:

- loads image and label data
- builds a small neural network
- performs training with gradient descent
- exports the trained weights and biases into JSON
- saves the model for Laravel/PHP inference

### neural_network_model.json / storage/app/neural_network_model.json

This is the trained model file.

It stores:

- input size
- hidden layer sizes
- output size
- weights
- biases

The PHP backend loads this file to predict digits without doing heavy training in the browser or during each request.

---

## 7. How the App Works

### Step 1: User draws a digit

The user writes a digit on the HTML canvas using the mouse or touch input.

### Step 2: Image is converted to data

The canvas is converted to an image data URL in base64 format and sent to the server.

### Step 3: Image preprocessing

On the server side, the controller:

- reads the image
- resizes it
- crops the drawing area
- converts it to grayscale
- normalizes values between 0 and 1
- arranges the data into a 784-element vector (28x28 pixels)

### Step 4: Neural network prediction

The PHP model receives the 784 input values and calculates:

- hidden layer activations
- second hidden layer activations
- output scores for digits 0 to 9

### Step 5: Softmax and confidence

The output is processed with a softmax function, creating probabilities for each digit.

The largest probability is selected as the predicted digit.

### Step 6: API response

The backend sends JSON such as:

```json
{
  "success": true,
  "predicted_digit": 7,
  "confidence": 92.3,
  "probabilities": [0.1, 0.2, 0.3, ...]
}
```

The frontend displays the result and the confidence percentage.

---

## 8. Neural Network Design

The project uses a multi-layer perceptron (MLP) with a simple structure:

- Input layer: 784 neurons
- Hidden layer 1: 128 neurons
- Hidden layer 2: 64 neurons
- Output layer: 10 neurons (for digits 0-9)

The main activation function is the sigmoid function, and the final output probabilities are generated using softmax.

This is a classic and educational architecture for simple image recognition tasks.

---

## 9. Required Environment

Before running the project, make sure you have:

- PHP 8.1 or newer
- Composer
- Node.js and npm
- Python 3.8+
- NumPy installed in Python
- A local development server environment

---

## 10. Installation Steps

### 1. Clone or download the repository

```bash
git clone <your-repository-link>
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

If the project does not already contain a working environment file, run:

```bash
cp .env.example .env
```

Then set your app key:

```bash
php artisan key:generate
```

### 5. Run the Laravel app

```bash
php artisan serve
```

Open the app in the browser at:

```text
http://localhost:8000
```

### 6. Run the frontend asset server in development mode

If Vite is used for frontend assets, run:

```bash
npm run dev
```

---

## 11. Model Training (Optional but Important)

This project can be trained with the included Python script.

### Requirements

- Python installed
- NumPy installed

### Install NumPy

```bash
pip install numpy
```

### Prepare the dataset

The project currently contains MNIST training files in the root directory:

- train-images-idx3-ubyte.gz
- train-labels-idx1-ubyte.gz
- t10k-images-idx3-ubyte.gz
- t10k-labels-idx1-ubyte.gz

If the files are missing, download the MNIST dataset from the official source and place them in the project root.

### Run the training script

```bash
python train_mnist_python.py
```

This will:

- train the model
- export weights and biases to JSON
- save the model in the project storage directory

---

## 12. Runtime Notes and Current Project State

This is a lightweight, educational project rather than a full production-grade ML platform.

Important notes:

- It is suitable for learning and demonstration
- The model is not a large deep-learning system like a CNN
- The app is intentionally simple and easy to understand
- The model handles handwritten digit recognition in a classical way
- Training can be improved by using more epochs, more data, or a better architecture

---

## 13. Current Project Strengths

- Easy to understand and extend
- Good for learning neural network fundamentals
- Clean Laravel structure
- Real digit recognition flow from frontend to backend
- Model and weights stored in JSON for later loading
- Good example for a portfolio or university project

---

## 14. Possible Improvements

Here are some good future upgrades:

- add a better CNN architecture
- use a larger and more accurate training dataset
- add data augmentation
- improve preprocessing for better handwriting accuracy
- add model evaluation metrics and accuracy charts
- add API documentation
- add tests for the controller and prediction logic
- add a proper admin or training panel

---

## 15. GitHub Preparation

To upload this project to GitHub, follow these steps:

### 1. Initialize Git (if needed)

```bash
git init
```

### 2. Check the status

```bash
git status
```

### 3. Add files to staging

```bash
git add .
```

### 4. Commit the project

```bash
git commit -m "Initial handwritten digit recognition project"
```

### 5. Create a repository on GitHub

Go to GitHub and create a new public or private repository.

### 6. Link the remote repository

```bash
git remote add origin <your-github-repository-url>
```

### 7. Push the code

```bash
git branch -M main
git push -u origin main
```

---

## 16. Recommended GitHub Repository Contents

When publishing the project, it is a good idea to include:

- README.md
- SETUP.md
- DOCUMENTATION.md
- .gitignore
- LICENSE (optional)
- screenshots or demo video (optional)

This makes the project easier for others to understand and evaluate.

---

## 17. Best Practices for a Portfolio Project

If this project is meant for a GitHub portfolio, consider:

- writing a clear project description
- including screenshots or GIFs of the app in action
- explaining the neural network architecture in simple words
- mentioning the programming languages and tools used
- documenting setup and installation instructions
- showing the expected result and limitations

---

## 18. Summary

This project is a complete beginner-friendly handwritten digit recognition app built with Laravel, PHP, JavaScript, Python, and NumPy. It demonstrates how a simple neural network can classify digits drawn by a user in the browser.

It is a strong project for:

- learning artificial neural networks
- practicing full-stack web development
- building a portfolio-ready machine learning project
- understanding the connection between Python model training and backend web deployment

---

## 19. Final Notes

This project is ideal for educational and portfolio purposes. It is simple, understandable, and practical, and it is a great example of how AI and web development can work together.

If you want to improve it later, you can evolve it into a deeper and more advanced model by adding:

- CNN architecture
- better training data
- real-time model evaluation
- a stronger frontend dashboard
- API-based model serving

If you are ready to upload it to GitHub, this project already has the structure needed for a clean and professional repository.
