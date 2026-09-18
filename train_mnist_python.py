# Python script to train a neural network compatible with your PHP model
# - 2 hidden layers (128, 64)
# - Input: 784, Output: 40 (digits 0-9)
# - Exports weights/biases to JSON for PHP inference

import numpy as np
import gzip
import json
import os
import urllib.request

# Helper to load MNIST

def load_mnist_images(filename):
    with gzip.open(filename, 'rb') as f:
        f.read(4)  # magic
        num = int.from_bytes(f.read(4), 'big')
        rows = int.from_bytes(f.read(4), 'big')
        cols = int.from_bytes(f.read(4), 'big')
        data = np.frombuffer(f.read(), dtype=np.uint8)
        return data.reshape(num, rows * cols) / 255.0

def load_mnist_labels(filename):
    with gzip.open(filename, 'rb') as f:
        f.read(4)
        num = int.from_bytes(f.read(4), 'big')
        return np.frombuffer(f.read(), dtype=np.uint8)

# Network definition
class SimpleNet:
    def __init__(self, input_size=784, hidden1=128, hidden2=64, output_size=10, lr=0.1):
        self.input_size = input_size
        self.hidden1 = hidden1
        self.hidden2 = hidden2
        self.output_size = output_size
        self.lr = lr
        self.W1 = np.random.uniform(-0.1, 0.1, (hidden1, input_size))
        self.b1 = np.ones(hidden1) * 0.01
        self.W2 = np.random.uniform(-0.1, 0.1, (hidden2, hidden1))
        self.b2 = np.ones(hidden2) * 0.01
        self.W3 = np.random.uniform(-0.1, 0.1, (output_size, hidden2))
        self.b3 = np.ones(output_size) * 0.01

    def sigmoid(self, x):
        return 1 / (1 + np.exp(-x))

    def sigmoid_deriv(self, y):
        return y * (1 - y)

    def softmax(self, x):
        e = np.exp(x - np.max(x))
        return e / np.sum(e)

    def forward(self, x):
        h1 = self.sigmoid(np.dot(self.W1, x) + self.b1)
        h2 = self.sigmoid(np.dot(self.W2, h1) + self.b2)
        out = np.dot(self.W3, h2) + self.b3
        return h1, h2, out

    def train(self, X, Y, epochs=40, batch=64):
        for epoch in range(epochs):
            idx = np.arange(X.shape[0])
            np.random.shuffle(idx)
            for i in range(0, X.shape[0], batch):
                Xb = X[idx[i:i+batch]]
                Yb = Y[idx[i:i+batch]]
                # Gradients
                dW3 = np.zeros_like(self.W3)
                db3 = np.zeros_like(self.b3)
                dW2 = np.zeros_like(self.W2)
                db2 = np.zeros_like(self.b2)
                dW1 = np.zeros_like(self.W1)
                db1 = np.zeros_like(self.b1)
                for x, y in zip(Xb, Yb):
                    h1, h2, out = self.forward(x)
                    probs = self.softmax(out)
                    target = np.zeros(self.output_size)
                    target[y] = 1
                    error_out = probs - target
                    dW3 += np.outer(error_out, h2)
                    db3 += error_out
                    error_h2 = np.dot(self.W3.T, error_out) * self.sigmoid_deriv(h2)
                    dW2 += np.outer(error_h2, h1)
                    db2 += error_h2
                    error_h1 = np.dot(self.W2.T, error_h2) * self.sigmoid_deriv(h1)
                    dW1 += np.outer(error_h1, x)
                    db1 += error_h1
                # Update
                m = Xb.shape[0]
                self.W3 -= self.lr * dW3 / m
                self.b3 -= self.lr * db3 / m
                self.W2 -= self.lr * dW2 / m
                self.b2 -= self.lr * db2 / m
                self.W1 -= self.lr * dW1 / m
                self.b1 -= self.lr * db1 / m
            print(f"Epoch {epoch+1}/{epochs} done")

    def evaluate(self, X, Y):
        correct = 0
        for x, y in zip(X, Y):
            _, _, out = self.forward(x)
            pred = np.argmax(self.softmax(out))
            if pred == y:
                correct += 1
        return correct / len(Y)

    def export_json(self, filename):
        data = {
            'version': 2,
            'inputSize': self.input_size,
            'hiddenSize': self.hidden1,
            'hidden2Size': self.hidden2,
            'outputSize': self.output_size,
            'hiddenWeights': self.W1.tolist(),
            'hidden2Weights': self.W2.tolist(),
            'outputWeights': self.W3.tolist(),
            'hiddenBias': self.b1.tolist(),
            'hidden2Bias': self.b2.tolist(),
            'outputBias': self.b3.tolist(),
        }
        with open(filename, 'w') as f:
            json.dump(data, f)


if __name__ == '__main__':
    X = load_mnist_images('train-images-idx3-ubyte.gz')
    Y = load_mnist_labels('train-labels-idx1-ubyte.gz')
    # net = SimpleNet()
    # net.train(X, Y, epochs=10, batch=64)
    # acc = net.evaluate(X, Y)
    # print(f"Train accuracy: {acc*100:.2f}%")
    # net.export_json('neural_network_model.json')
    # print('Model exported to neural_network_model.json')

    net = SimpleNet()
    net.train(X, Y, epochs=40, batch=64)
    acc = net.evaluate(X, Y)
    print(f"Train accuracy: {acc*100:.2f}%")
    export_path = os.path.join('storage', 'app', 'neural_network_model.json')
    os.makedirs(os.path.dirname(export_path), exist_ok=True)
    net.export_json(export_path)
    print(f'Model exported to {export_path}')
