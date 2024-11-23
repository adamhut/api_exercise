# NYT Best Sellers API Integration

A Laravel-based API wrapper for the New York Times Best Sellers API that provides endpoints to search and retrieve best-selling books information.

## 📋Features

- Search best sellers by ISBN (single or multiple)
- Filter by author and title
- Pagination support with offset
- Comprehensive error handling
- Request validation
- Detailed logging

## Requirements

- PHP 8.2 or higher
- Laravel 10.x
- Composer
- New York Times API Key

## Installation

1. Clone the repository:
```bash
git clone https://github.com/adamhut/api_exercise.git
cd api_exercise
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Configure your NYT API credentials in `.env`:
```env
NEW_YORK_TIMES_API_URL=https://api.nytimes.com
NEW_YORK_TIMES_API_KEY=your-api-key-here
```

## Usage

### API Endpoints

#### GET /api/best-sellers

Search for best-selling books with various filters.

**Parameters:**

| Parameter | Type | Description | Format |
|-----------|------|-------------|---------|
| isbn[] | array | Book ISBN(s) | 10 or 13 digits |
| author | string | Author name | max 255 chars |
| title | string | Book title | max 255 chars |
| offset | integer | Pagination offset | Multiple of 20 |

**Example Request:**
```http
GET /api/best-sellers?isbn[]=0593836324&author=John%20Doe&offset=20
```

**Success Response:**
```json
{
    "status": "OK",
    "results": [
        {
            "title": "Example Book",
            "author": "John Doe",
            "isbn": "0593836324"
        }
    ]
}
```

**Error Response:**
```json
{
    "error": "Rate limit exceeded. Please try again later."
}
```

##  Testing

The project includes comprehensive tests for all functionality. Run them using:

```bash
php artisan test
```


##  License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- [New York Times API Documentation](https://developer.nytimes.com/docs/books-product/1/overview)
- Laravel Framework
- Contributors



##