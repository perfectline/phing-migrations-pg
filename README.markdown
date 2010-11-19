Phing PostgreSQL Migrations Task
================================

Basic migrations support for Phing. This task is only for PostgreSQL and currently only supports one way migrations (no rollback).

Configuration
-------------

Add MigrationTask.php to your project:

    <?xml version="1.0" encoding="UTF-8"?>
    <project name="phing-migrations-pg" basedir="." default="db_migrate">

        <taskdef name="db_migrate" classname="MigrationTask"/>

        <!-- database host -->
        <property name="db.host" value="localhost"/>

        <!-- database port, optional -->
        <property name="db.port" value="5432"/>

        <!-- database name -->
        <property name="db.name" value="phing-migrations-pg"/>

        <!-- database username, optional -->
        <property name="db.user" value=""/>

        <!-- database password, optional -->
        <property name="db.pass" value=""/>

        <!-- directory where migrations are located -->
        <property name="db.path" value="migrations"/>


        <target name="db_migrate">

            <db_migrate host="${db.host}"
                        port="${db.port}"
                        name="${db.name}"
                        user="${db.user}"
                        pass="${db.pass}"
                        path="${db.path}"/>

        </target>

    </project>



Basic DDL
---------

*001_create_users.php:*

    <?php

    $this->execute("CREATE TABLE users (
        id              SERIAL          PRIMARY KEY,
        login           VARCHAR(255)    NOT NULL,
        password        VARCHAR(32)     NOT NULL,
        first_name      VARCHAR(255)    NOT NULL,
        last_name       VARCHAR(255)    NOT NULL,
        created_at      TIMESTAMP,
        updated_at      TIMESTAMP
    )");

    $this->execute("CREATE UNIQUE INDEX users_login_index ON users(login)");

    ?>

*002_create_addresses.php:*

    <?php

    $this->execute("CREATE TABLE addresses (
        id              SERIAL          PRIMARY KEY,
        user_id         INTEGER         NOT NULL,
        house           VARCHAR(255)    NOT NULL,
        street          VARCHAR(255)    NOT NULL,
        county          VARCHAR(255)    NOT NULL,
        country         VARCHAR(255)    NOT NULL
    )");

    $this->execute("CREATE INDEX addresses_user_id_index ON addresses(user_id)");
    $this->execute("CREATE INDEX addresses_county_index ON addresses(county)");
    $this->execute("CREATE INDEX addresses_country_index ON addresses(country)");

    $this->execute("ALTER TABLE addresses ADD FOREIGN KEY(user_id) REFERENCES users(id)");

    ?>


Data conversion
---------------

*003_convert_addresses.php:*

    <?php

    foreach($this->query("SELECT * FROM addresses") as $address){
        // do something with address
    }

    $x = $this->queryOne("SELECT x FROM maps WHERE id = 4");
    $y = $this->queryOne("SELECT y FROM maps WHERE id = 4");

    // do something with x and y
    
    ?>


Authors
-------

**Tanel Suurhans** (<http://twitter.com/tanelsuurhans>)
**Tarmo Lehtpuu** (<http://twitter.com/tarmolehtpuu>)

License
-------
Copyright 2010 by PerfectLine LLC (<http://www.perfectline.co.uk>) and is released under the MIT license.