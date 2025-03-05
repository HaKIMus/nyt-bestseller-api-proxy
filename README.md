# ADR
**Simplified**

## Versioning

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