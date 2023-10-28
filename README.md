# Captain

more commands and tools for Codeigniter 4

- Route Generator
- Symlink Generator (uploads directory)
- Database migration generator

## Migration generator usage

1. Create database.conf file in root
2. in each line, write your table and its fields
3. Run command `php spark db:migrations` to generator migrations

### Database.conf example
```
users = id name email password:text is_active dates(=created_at,updated_at,deleted_at)
posts = id title body:text type:index user_id published_at dates
```