# Symfony console example

This is basic example of usage Symfony console with Docker and database (DBAL without ORM). There is only one `CountDomainsCommand` which iterates over `users` table and counts how many users are using each email domain.

## How To Run

Run the database via Docker Compose: `docker-compose up -d`. It will launch MySQL server with schema and data placed in project's [mysql-dump](mysql-dump) directory.

Since the base is launched, build localy docker image of this app.

```sh
docker build -t kfreiman/php-cli-docker-example .
```

Note, in [Dockerfile](Dockerfile) used [multi-stage build](https://docs.docker.com/develop/develop-images/multistage-build/) to minimize size of container.

Finally, you can run the app.

```sh
docker run \
  --link=mysql_test_db:db \
  --network=custom_network \
  -e MYSQL_DATABASE=test_db \
  -e MYSQL_USER=test_user \
  -e MYSQL_PASSWORD=secret \
  -e batch=3 \
  kfreiman/php-cli-docker-example
```

To access the database container from app's container, you must specify `--link` parameter and enviroment variables from `docker-compose.yml`. Parameter `--network` is needed to run the container on the same network which as Docker Compose. `-e batch=3` defines batch size per table's query iteration, it's equal `1000` by default.

It will prints count of users, which uses certain domain:

```sh
5 domain1.com
4 domain4.com
2 domain2.com
1 domain3.com
```

## Possible Future Improvements

- Use entrypoint to able run different commands
- Use lock table or updated_at mark for cases the table is big and some changes is possible during the command execution
- Implement tests
- Show progress during execution
