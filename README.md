# What I'd do differently
**given more time**

1. I'd figure out rate limiter better on both sides. 
2. I'd implement a circuit breaker pattern on the client side.
3. I'd inform users if a result comes from a cache.
4. I'd rewrite tests
   1. I'd utlise data providers
   2. I'd improve readability and reusability of the components
5. I had a plan to add an integration with an api model to optionally  create a summary of a book, like key points. Users would need to provide an api key for this functionality.
6. I'd add an endpoint to get a list of cached results - I'm not sure what I'd it for, but maybe the API users would know.

# ADR
**Simplified**

## Versioning

First of all - I'm aware of complexity of the `App\NewYorkTimes\UserInterface\Api\V1\Resource\StableBestsellerResource` class.
Given more time I'd refactor it to something more approachable. I've created it to validate my idea on the api's manifesto about breaking changes.

1. I've decided to go for routing & namespace-based versioning due to my decision in manifesto that should reduce the amount of breaking changes.
2. Version update (v1 -> v2 -> v3) occurs only on breaking changes.

---

## Support for both internal and client's api key

This way I highly increase the API limits by utilizing the cache.

# Manifesto

This API MUST change only due to external service breaking change of the following categories:
1. Field deletion
2. Deleted field name re-usage
3. Adding a new field

**Deleted field name re-usage**\
As the 1st and the 3rd points are self-explanatory, let me explain the 2nd one.

If external service makes a change renaming a field name from "x" to "y", we're not going to change anything in ours API as if the change did not happen.
If, however, a new field would be introduced as of name "y", we'd add a new field with a different but descriptive name to "y".

Example

Init response:

```json
{
     "title": "Harry Potter" 
}
```

NYT changed field "title" to "book_title".\
THIS PROXY DOESN'T CHANGE ANYTHING IN ITS API.

```json
{
     "title": "Harry Potter" 
}
```

NYT added a new field "title".\
THIS PROXY ADDS A NEW FIELD NAMED **LIKE** "article_title" (if the original title was in fact an article title covering the content of the book).

```json
{
    "title": "Harry Potter",
    "article_title": "Harry Potter - The magic book everyone loves"
}
```

# How to run it

1. Clone the repository
2. Run `./vendor/bin/sail up`
   1. If you have docker not installed on your device visit:  https://docs.docker.com/engine/install/
3. Retrieve New York Times api key
   4. On how to do this read section below [Create your own API credentials to access the NYT API](#create-your-own-api-credentials-to-access-the-nyt-api)
4. To get the bestseller list visit: `http://localhost/api/v1/bestsellers`
   1. To look for a more specific ISBN filter by:  `http://localhost/api/v1/bestsellers?isbn[]=ISBN_NUMBER`
5. To run tests exec the following command: ` docker compose exec -it laravel.test php artisan test`

## Create your own API credentials to access the NYT API:
1. Create a New York Times developer account: https://developer.nytimes.com/accounts/create
2. Go to create a New App: https://developer.nytimes.com/my-apps/new-app
3. Enable the Books API.
4. Create your app.
5. Copy your API key locally. 

If any issue occurs, please let me know, and we'll prompt the new claude model together! 🚀