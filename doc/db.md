## Database scheme
### Visual representation
![image](https://github.com/user-attachments/assets/b307f3cd-d9cc-49cf-af86-47c7d2b45dae)


### DBML representation
```sql
/*
/*
one user can have many subscriptions (to many authors)
one author has many subscriptions (of many users)
*/
Table Subscription {
  user_id int [ref: > User.id]
  author_user_id int [ref: > User.id]
}

/*
one user can leave many likes (one like under many articles)
one article has many likes

the name `LikeReaction` like was chosen in fear of SQL's `LIKE` keyword collision
*/
Table LikeReaction {
  user_id int [ref: > User.id]
  article_id int [ref: > Article.id]
}

/*
one user can leave many comments
one article has many comments
*/
Table Comment {
  user_id int [ref: > User.id]
  article_id int [ref: > Article.id]
}

/*
one author can write many articles
*/
Table Article {
  id int [primary key]
  author_user_id int [ref: > User.id]
  title varchar
  content text
  tags text [null]
}

Table User {
  id int [primary key]
  username varchar [unique]
  email varchar [unique]
  password varchar
  role enum('user', 'admin', 'superadmin')
  email_verified_at timestamp
  created_at timestamp
  updated_at timestamp
}

Table Profile {
  user_id int [ref: - User.id]
  description text [null]
  logo varchar [null]
}

