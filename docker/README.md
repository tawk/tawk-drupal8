Drupal
============
Docker containers for Drupal

## Information
- Drupal Versions: 8.8, 8.9, 9
- MySQL Version: 5.7

## Pre-Requisites
- install docker-compose [http://docs.docker.com/compose/install/](http://docs.docker.com/compose/install/)

## Usage
Start the container:
- ```docker-compose --env-file envs/<env-file> up```

Start the container in detach mode:
- ```docker-compose --env-file envs/<env-file> up -d```

Stop the container:
- ```docker-compose --env-file envs/<env-file> stop```

Destroy the container and start from scratch:
- ```docker-compose --env-file envs/<env-file> down```
- ```docker volume rm drupal-<volume-name>```
    - ex. ```docker volume rm drupal-latest_web_data drupal-latest_db_data```

## Plugin setup
You can follow the instruction in [Drupal 8 Github Repo](https://github.com/tawk/tawk-drupal8) (Drupal 9 also works here since it is backwards compatible).
