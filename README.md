![LISSA logo](doc/logo.png)

# LISSA Kickstart Distribution

LISSA is an open source technology stack for real time messaging in second
screen applications. It allows media providers to publish live and on-demand
video streams and push related notifications to clients.

The LISSA Kickstart profile is a reusable Drupal 8 distribution for managing
and publishing events and notifications. It comes pre-configured with the
following functionality:

- Real-time push message API through the LISSA backend stack for publishing
  live notifications.
- REST API for publishing events.
- REST API for publishing on-demand notifications.
- A proof-of-concept for soccer matches with contextual soccer statistics.
- Twitter integration for pushing notifications to a Twitter account.

LISSA is separated into four projects:

- [Kickstart](https://github.com/oneagency/lissa_kickstart): The Drupal installation profile for the backend interface.
- [Infrastructure](https://github.com/oneagency/lissa_infrastructure): Vagrant and Chef scripts for provisioning a box with the services needed for running LISSA.
- [Deploy](https://github.com/oneagency/lissa_deploy): Capistrano script for deploying LISSA Kickstart to a Vagrant box or remote servers.
- [Worker](https://github.com/oneagency/lissa_worker): PHP script that parses and forwards notifications from a message queue to a push stream server.

## Installation

### Using Vagrant

The recommended way to install the LISSA distribution is by using the LISSA
[infrastructure](https://github.com/oneagency/lissa_infrastructure) and
[deploy](https://github.com/oneagency/lissa_deploy) repositories on GitHub.
The infrastructure repo will provision a Vagrant box containing all the
required services and the deploy repo will install and configure a Drupal 8
instance with the LISSA Kickstart distribution.

#### Requirements

- Bundler
- OSX: Xcode command line tools: xcode-select --install
- Virtualbox 4.3.10+
- Vagrant 1.6.3
- vagrant-omnibus plugin
- vagrant-hostsupdater plugin
- Git

#### Vagrant setup

```bash
git clone https://github.com/oneagency/lissa_infrastructure
cd lissa_infrastructure
vagrant up --provision`
```

#### Drupal installation

```bash
git clone https://github.com/oneagency/lissa_deploy
cd lissa_deploy
bundle install
bundle exec cap local deploy
```

When Drupal has been installed you can go to <http://admin.lissa.dev> and log in with admin:admin.

### Using Phing

You can set up the Drupal distribution with Phing following these steps:

- cd to the root of this repository
- Execute the following command: phing -Ddocroot=/path/to/docroot
- Replace /path/to/docroot with the path of your virtual host.

The following steps will be executed:

- Create a docroot directory under /path/to/docroot
- Execute drush make on the build.make file
  - Drush make will set up drupal 8 core
  - Drush make will add the lissa_kickstart profile to the profiles directory
  - Drush make will execute the lissa_kickstart.make file
- Execute drush site-install with the parameters provided in
  build.defaults.properties

### Manual Installation

If you decide to install LISSA Kickstart manually please keep the following
things in mind:

- Use the Drupal core version specified in the build.make file. Other versions
  of Drupal 8 may not be compatible.
- Install the contrib modules specified in the lissa_kickstart.make file.
- Install the services (see the [infrastructure repo](https://github.com/oneagency/lissa_infrastructure)
  for versions and configuration):
  - A RabbitMQ server
  - An Nginx push stream server
  - A service running the [worker PHP script](https://github.com/oneagency/lissa_worker)


### Server Installation

You can provision your own server for a LISSA Kickstart installation by using
the [infrastructure](https://github.com/oneagency/lissa_infrastructure) repo
on GitHub. This contains a set of Chef cookbooks with all the necessary services
for running the full LISSA technology stack.

After provisioning a server you can clone the
[deploy](https://github.com/oneagency/lissa_deploy) repository for deploying the
distribution using capistrano.

#### Distributed Servers

The [infrastructure](https://github.com/oneagency/lissa_infrastructure) repo
has support for provisioning the backend across multiple servers. See the
documentation included with the repository for more information.

## Configuration

### AMQP Server

By default LISSA Kickstart will push its notifications to an AMQP server running on localhost:15672. You can change the address and credentials on <http://admin.lissa.dev/admin/config/services/notification-push/settings>.

### Twitter

Twitter messages are by default pushed by a locked demo account. You can use another Twitter account by changing the settings at <http://admin.lissa.dev/admin/config/services/notification-twitter/settings>.

## Demo

You can test the application using the demo web client located at <http://admin.lissa.dev/profiles/lissa_kickstart/test/client/demo.html>.

The test web application will:

- Load all events from the Drupal 8 REST event API.
- Load all existing notifications from the Drupal 8 REST notification API.
- Set up websocket connections to all events.

Notifications added to events will show up automatically in the test web application.

## Architecture

![LISSA Component Diagram](doc/component-diagram.png)

The LISSA stack is divided in the following components:

### Drupal 8

The administration backend where operators manage events and notifications.
Using the views and rest modules it also provides a REST API for fetching all
published events and past notifications.

Runs on port 80 in the default infrastructure single server setup, login with admin/admin in the local setup:

<http://admin.lissa.dev>

### RabbitMQ MessageQueue

A message queue for storing and forwarding the real time notifications to
clients. External services like Facebook, Twitter or other Drupal sites can
plugin to the queue to send additional data in real time to clients.

Runs on port 15672 in the default infrastructure single server setup, login with guest/guest in the local setup:

<http://admin.lissa.dev:15672>

### PHP Worker

Parses the notifications from the message queue and forwards them to the nginx
push stream server. This can be used for additional processing prior to sending
the data to clients. It also increases scalability by providing multiple
workers.

There's an implementation that is set up either using the infrastructure repo or
by running the worker.php script from the [worker repo](https://github.com/oneagency/lissa_worker)
using a process manager like supervisord.

### Nginx push stream server

Allows websocket connections for pushing the notification to clients.

Runs on port 8080 with the following endpoints:

- /publish/uuid: publish notifications to all clients
- /ws/uuid: websocket endpoint for clients.

uuid can be replaced by the UUID of the event node a clients wants to receive
notifications from.

## APIs

### Events API

The events API is a JSON feed listing all public event nodes and their meta data. It can be accessed using the following method:

- HTTP GET request to http://admin.lissa.dev/api/events
- Extra request header: Accept: application/ext+json

Without the header only a limited set of data will be returned. The extended format will also load data for referenced entities (images, players, teams, etc.).

Example JSON output:

```json
[{
  "uuid": [{
    "value": "6f0359f9-4252-471f-bd09-9d284110d92e"
  }],
  "type": [{
    "target_id": "soccer_event"
  }],
  "langcode": [{
    "value": "en"
  }],
  "title": [{
    "value": "MUN - CHE",
    "lang": "en"
  }],
  "status": [{
    "value": "1",
    "lang": "en"
  }],
  "created": [{
    "value": "1411551574",
    "lang": "en"
  }],
  "changed": [{
    "value": "1421930410",
    "lang": "en"
  }],
  "promote": [{
    "value": "1",
    "lang": "en"
  }],
  "sticky": [{
    "value": "0",
    "lang": "en"
  }],
  "revision_timestamp": [{
    "value": "1411551574"
  }],
  "revision_log": [{
    "value": "",
    "lang": "en"
  }],
  "field_event_away_team": [{
    "uuid": [{
      "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
    }],
    "vid": [{
      "target_id": "team"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "CHE",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421230500"
    }],
    "field_team_color": [{
      "value": "#0077B4"
    }]
  }],
  "field_event_competition": [{
    "uuid": [{
      "value": "77da5380-eb94-480c-bb1d-292ce39135e6"
    }],
    "vid": [{
      "target_id": "competition"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "Pro League",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1418290608"
    }]
  }],
  "field_event_encoder_on": [{
    "value": "1"
  }],
  "field_event_endpoint": [{
    "url": "http:\/\/demo.lissa.one-agency.be\/crosscheck\/manifest.xml",
    "title": "",
    "route_name": null,
    "route_parameters": [],
    "options": {
      "external": true
    }
  }],
  "field_event_hide": [{
    "value": "2015-09-26T23:00:00"
  }],
  "field_event_home_team": [{
    "uuid": [{
      "value": "d76b3d0a-e2b4-431b-a000-cb0fb2804635"
    }],
    "vid": [{
      "target_id": "team"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "MUN",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421230520"
    }],
    "field_team_color": [{
      "value": "#D31E2F"
    }]
  }],
  "field_event_interface": [{
    "value": "0"
  }],
  "field_event_match_start": [{
    "value": "2014-12-12T15:30:00"
  }],
  "field_event_team_setup": [{
    "uri": [{
      "value": "http:\/\/demo.lissa.one-agency.be\/sites\/default\/files\/event-setup.png"
    }]
  }],
  "field_event_thumbnail": [{
    "uri": [{
      "value": "http:\/\/demo.lissa.one-agency.be\/sites\/default\/files\/event-thumbnail.png"
    }]
  }],
  "field_event_timeline_end": [{
    "value": "2014-12-12T16:00:00"
  }],
  "field_event_timeline_start": [{
    "value": "2014-12-12T15:30:00"
  }],
  "field_event_twitter": [{
    "value": "#mun"
  }],
  "field_event_visible": [{
    "value": "2014-09-24T17:00:00"
  }]
}]
```

### Notification Replay API

The notification replay API is a JSON feed that returns existing notifications for a given event. It can be used to replay an event or allow users to watch an already ongoing event.

The API can be accessed using the following method:

- HTTP GET request to http://admin.lissa.dev/api/notifications/[event-uuid]
- Replace [event-uuid] by the uuid of the event you're requesting notifications for.
- Extra request header: Accept: application/ext+json

Example JSON output for notifications linked to the event in the previous example:

**URL:**

http://admin.lissa.dev/api/notifications/6f0359f9-4252-471f-bd09-9d284110d92e

**Output:**

```json
[{
  "uuid": [{
    "value": "a3456c47-ee9d-428e-8619-250adb43884f"
  }],
  "type": [{
    "target_id": "status"
  }],
  "title": [{
    "value": "Second half started",
    "lang": "und"
  }],
  "created": [{
    "value": "1418642633",
    "lang": "und"
  }],
  "changed": [{
    "value": "1421231379",
    "lang": "und"
  }],
  "timeline": [{
    "value": "2014-12-12T15:30:05"
  }],
  "image": [{
    "target_id": "0",
    "display": null,
    "description": null,
    "alt": null,
    "title": null,
    "width": null,
    "height": null,
    "lang": "und"
  }],
  "field_status_type": [{
    "value": "Second half started"
  }],
  "api_meta": {
    "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
    "type": "create"
  },
  "soccer_stats": []
}, {
  "uuid": [{
    "value": "e2a6cfd3-9c22-450f-8000-dcfca66ea189"
  }],
  "type": [{
    "target_id": "card"
  }],
  "title": [{
    "value": "Yellow card for Fabregas",
    "lang": "und"
  }],
  "created": [{
    "value": "1421231380",
    "lang": "und"
  }],
  "changed": [{
    "value": "1421231502",
    "lang": "und"
  }],
  "timeline": [{
    "value": "2014-12-12T15:32:50"
  }],
  "image": [{
    "target_id": "0",
    "display": null,
    "description": null,
    "alt": null,
    "title": null,
    "width": null,
    "height": null,
    "lang": "und"
  }],
  "content": [{
    "value": "Yellow card for bad foul.",
    "format": "full_html"
  }],
  "field_card_player": [{
    "uuid": [{
      "value": "3d159e09-bebc-469a-b300-eb01ce065cb9"
    }],
    "vid": [{
      "target_id": "player"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "Fabregas",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421231361"
    }],
    "field_player_teams": [{
      "uuid": [{
        "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
      }],
      "vid": [{
        "target_id": "team"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "CHE",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1421230500"
      }],
      "field_team_color": [{
        "value": "#0077B4"
      }],
      "api_meta": {
        "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
        "type": "create"
      },
      "soccer_stats": []
    }],
    "api_meta": {
      "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
      "type": "create"
    },
    "soccer_stats": []
  }],
  "field_card_type": [{
    "value": "Yellow"
  }],
  "api_meta": {
    "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
    "type": "create"
  },
  "soccer_stats": []
}, {
  "uuid": [{
    "value": "e5594983-10a2-4099-a800-c681f3ad85c5"
  }],
  "type": [{
    "target_id": "goal"
  }],
  "title": [{
    "value": "Goal by Drogba",
    "lang": "und"
  }],
  "created": [{
    "value": "1421231502",
    "lang": "und"
  }],
  "changed": [{
    "value": "1421748801",
    "lang": "und"
  }],
  "timeline": [{
    "value": "2014-12-12T15:35:50"
  }],
  "image": [{
    "target_id": "0",
    "display": null,
    "description": null,
    "alt": null,
    "title": null,
    "width": null,
    "height": null,
    "lang": "und"
  }],
  "content": [{
    "value": "Didier Drogba header from the left side of the six yard box to the top left corner.",
    "format": "full_html"
  }],
  "field_goal_player": [{
    "uuid": [{
      "value": "5a94ccb1-3baa-4341-8200-d78000ac5b51"
    }],
    "vid": [{
      "target_id": "player"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "Drogba",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421231630"
    }],
    "field_player_teams": [{
      "uuid": [{
        "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
      }],
      "vid": [{
        "target_id": "team"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "CHE",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1421230500"
      }],
      "field_team_color": [{
        "value": "#0077B4"
      }],
      "api_meta": {
        "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
        "type": "create"
      },
      "soccer_stats": []
    }],
    "api_meta": {
      "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
      "type": "create"
    },
    "soccer_stats": []
  }],
  "field_goal_score": [{
    "value": "0 - 1"
  }],
  "field_goal_team": [{
    "uuid": [{
      "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
    }],
    "vid": [{
      "target_id": "team"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "CHE",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421230500"
    }],
    "field_team_color": [{
      "value": "#0077B4"
    }],
    "api_meta": {
      "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
      "type": "create"
    },
    "soccer_stats": []
  }],
  "api_meta": {
    "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
    "type": "create"
  },
  "soccer_stats": []
}, {
  "uuid": [{
    "value": "7e1b8d58-f12f-4e7b-854d-77aecd0b440a"
  }],
  "type": [{
    "target_id": "card"
  }],
  "title": [{
    "value": "Yellow card for Oscar",
    "lang": "und"
  }],
  "created": [{
    "value": "1421231698",
    "lang": "und"
  }],
  "changed": [{
    "value": "1421231742",
    "lang": "und"
  }],
  "timeline": [{
    "value": "2014-12-12T15:45:00"
  }],
  "image": [{
    "target_id": "0",
    "display": null,
    "description": null,
    "alt": null,
    "title": null,
    "width": null,
    "height": null,
    "lang": "und"
  }],
  "content": [{
    "value": "Yellow card for a bad foul.",
    "format": "full_html"
  }],
  "field_card_player": [{
    "uuid": [{
      "value": "83ffff8e-da1e-4ca6-9104-4b60f67ad09c"
    }],
    "vid": [{
      "target_id": "player"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "Oscar",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421231693"
    }],
    "field_player_teams": [{
      "uuid": [{
        "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
      }],
      "vid": [{
        "target_id": "team"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "CHE",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1421230500"
      }],
      "field_team_color": [{
        "value": "#0077B4"
      }],
      "api_meta": {
        "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
        "type": "create"
      },
      "soccer_stats": []
    }],
    "api_meta": {
      "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
      "type": "create"
    },
    "soccer_stats": []
  }],
  "field_card_type": [{
    "value": "Yellow"
  }],
  "api_meta": {
    "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
    "type": "create"
  },
  "soccer_stats": []
}, {
  "uuid": [{
    "value": "f2f6f55d-549e-48fc-9400-f84c69b6bbe5"
  }],
  "type": [{
    "target_id": "card"
  }],
  "title": [{
    "value": "Yellow card for Ivanovic",
    "lang": "und"
  }],
  "created": [{
    "value": "1421231779",
    "lang": "und"
  }],
  "changed": [{
    "value": "1421231807",
    "lang": "und"
  }],
  "timeline": [{
    "value": "2014-12-12T15:48:00"
  }],
  "image": [{
    "target_id": "0",
    "display": null,
    "description": null,
    "alt": null,
    "title": null,
    "width": null,
    "height": null,
    "lang": "und"
  }],
  "field_card_player": [{
    "uuid": [{
      "value": "24a7660d-453c-4194-af08-8a4e1d6343e8"
    }],
    "vid": [{
      "target_id": "player"
    }],
    "langcode": [{
      "value": "en"
    }],
    "name": [{
      "value": "Ivanovic",
      "lang": "en"
    }],
    "weight": [{
      "value": "0"
    }],
    "changed": [{
      "value": "1421231773"
    }],
    "field_player_teams": [{
      "uuid": [{
        "value": "e67158e6-99a7-4cba-bb00-e4f2473b472a"
      }],
      "vid": [{
        "target_id": "team"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "CHE",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1421230500"
      }],
      "field_team_color": [{
        "value": "#0077B4"
      }],
      "api_meta": {
        "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
        "type": "create"
      },
      "soccer_stats": []
    }],
    "api_meta": {
      "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
      "type": "create"
    },
    "soccer_stats": []
  }],
  "field_card_type": [{
    "value": "Yellow"
  }],
  "api_meta": {
    "event_uuid": "6f0359f9-4252-471f-bd09-9d284110d92e",
    "type": "create"
  },
  "soccer_stats": []
}]
```

### Message Queue

The message queue is an AMQP service that accepts notifications related to the events. The Drupal 8 backend will post the notification entity actions to this queue so the worker can forward them to the websocket connections.

| Type        | Address | Credentials |
| ----------- |--------------|------|
| RabbitMQ server	 | admin.lissa.dev:15672 | guest / guest |
| RabbitMQ queue | content_notification | |

### Notification Stream

The notifications are available via an [Nginx push stream](http://wiki.nginx.org/HttpPushStreamModule). Each event has its own channel (using the event UUID) where notifications will be sent to in JSON format.

There's an example client (JS) available on <http://admin.lissa.dev/profiles/lissa_kickstart/test/client/demo.html>

| Type | Value | URL |
|------|-------|-----|
| Websocket endpoint |	/ws |	admin.lissa.dev:8080/ws |
| Stream subscription endpoint |	/subscribe |	admin.lissa.dev:8080/subscribe |
| Channels (websocket) |	uuid of event |	admin.lissa.dev:8080/ws/event-uuid |

**Example JSON payload:**

```json
{
  "text": {
    "uuid": [{
      "value": "172a6a0c-216b-4865-ac00-a47e568ba5a3"
    }],
    "type": [{
      "target_id": "goal"
    }],
    "title": [{
      "value": "Goal by Maertens",
      "lang": "und"
    }],
    "created": [{
      "value": 1424158855,
      "lang": "und"
    }],
    "changed": [{
      "value": 1424158873,
      "lang": "und"
    }],
    "timeline": [{
      "value": "2015-02-17T08:41:45"
    }],
    "field_goal_player": [{
      "uuid": [{
        "value": "2e090f77-6306-4708-ac03-3b6fbc736e2b"
      }],
      "vid": [{
        "target_id": "player"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "Maertens",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1424131006"
      }],
      "field_player_first_name": [{
        "value": ""
      }],
      "field_player_teams": [{
        "uuid": [{
          "value": "f1c3e324-b1ec-419e-921d-29a030c90de1"
        }],
        "vid": [{
          "target_id": "team"
        }],
        "langcode": [{
          "value": "en"
        }],
        "name": [{
          "value": "Club Brugge",
          "lang": "en"
        }],
        "weight": [{
          "value": "0"
        }],
        "changed": [{
          "value": "1424131006"
        }],
        "field_team_color": [{
          "value": "#0077B4"
        }],
        "field_team_competitions": [{
          "uuid": [{
            "value": "c240bf18-9add-4d62-9251-819d7c93ee18"
          }],
          "vid": [{
            "target_id": "competition"
          }],
          "langcode": [{
            "value": "en"
          }],
          "name": [{
            "value": "Jupiler League",
            "lang": "en"
          }],
          "weight": [{
            "value": "0"
          }],
          "changed": [{
            "value": "1424131006"
          }],
          "api_meta": {
            "type": "create",
            "event_uuid": "8685158b-94c4-4c53-8237-559126994e9c"
          }
      }]
    }],
    "field_goal_score": [{
      "value": "1 - 0"
    }],
    "field_goal_team": [{
      "uuid": [{
        "value": "f1c3e324-b1ec-419e-921d-29a030c90de1"
      }],
      "vid": [{
        "target_id": "team"
      }],
      "langcode": [{
        "value": "en"
      }],
      "name": [{
        "value": "Club Brugge",
        "lang": "en"
      }],
      "weight": [{
        "value": "0"
      }],
      "changed": [{
        "value": "1424131006"
      }],
      "field_team_color": [{
        "value": "#0077B4"
      }],
      "field_team_competitions": [{
        "uuid": [{
          "value": "c240bf18-9add-4d62-9251-819d7c93ee18"
        }],
        "vid": [{
          "target_id": "competition"
        }],
        "langcode": [{
          "value": "en"
        }],
        "name": [{
          "value": "Jupiler League",
          "lang": "en"
        }],
        "weight": [{
          "value": "0"
        }],
        "changed": [{
          "value": "1424131006"
        }]
      }, {
        "uuid": [{
          "value": "77da5380-eb94-480c-bb1d-292ce39135e6"
        }],
        "vid": [{
          "target_id": "competition"
        }],
        "langcode": [{
          "value": "en"
        }],
        "name": [{
          "value": "Pro League",
          "lang": "en"
        }],
        "weight": [{
          "value": "0"
        }],
        "changed": [{
          "value": "1424131006"
        }]
    }],
    "soccer_stats": {
      "field_goal_team": {
        "nid": [{
          "value": "6"
        }],
        "uuid": [{
          "value": "a691d7ed-e5e2-426b-8a00-fdd19585ebaa"
        }],
        "vid": [{
          "value": "6"
        }],
        "type": [{
          "target_id": "soccer_team_stats"
        }],
        "langcode": [{
          "value": "en"
        }],
        "title": [{
          "value": "Club Brugge - 2013\/2014"
        }],
        "uid": [{
          "target_id": "0"
        }],
        "status": [{
          "value": "1"
        }],
        "created": [{
          "value": "1413373304"
        }],
        "changed": [{
          "value": "1424131006"
        }],
        "promote": [{
          "value": "0"
        }],
        "sticky": [{
          "value": "0"
        }],
        "revision_timestamp": [{
          "value": "1413373304"
        }],
        "revision_uid": [{
          "target_id": "0"
        }],
        "revision_log": [{
          "value": ""
        }],
        "path": [
          []
        ],
        "field_stats_defeats": [{
          "value": "10"
        }],
        "field_stats_draws": [{
          "value": "0"
        }],
        "field_stats_matches": [{
          "value": "12"
        }],
        "field_stats_season": [{
          "target_id": "11"
        }],
        "field_stats_team": [{
          "target_id": "3"
        }],
        "field_stats_wins": [{
          "value": "90"
        }]
      }
    }
  },
  "tag": "create",
  "id": 1,
  "channel": "8685158b-94c4-4c53-8237-559126994e9c"
}
```

#### Testing the stream
- Go to <http://admin.lissa.dev/profiles/lissa_kickstart/test/client/demo.html>
- Enter admin.lissa.dev for the admin server and admin.lissa.dev:8080 for the worker server
- Submit the form
- Go to <http://admin.lissa.dev>
- Login with admin / admin
- Go to <http://admin.lissa.dev/node/4/timeline>
- Add notifications

When submitting the web client form the following will happen:

- Events are loaded via AJAX using the event API
- For each event the notification replay API is loaded to fetch existing notifications
- For each event a websocket channel is openend using the event UUID

#### Using the websocket

- Connect to the websocket on admin.lissa.dev:8080/ws
- Subscribe to the channel using the event uuid you want to get messages for.

#### Monitoring

You can monitor in- and outgoing notifications in the RabbitMQ control panel.

- Go to <http://admin.lissa.dev:15672>
- Login with guest / guest
- Go to <http://admin.lissa.dev:15672/#/queues/%2F/content_notification>
- When you add a notification it will be queued and automatically consumed

#### Background info

When adding a notification in the Drupal backend it will follow this path:

1. Notification entity is added to the Drupal database
2. Drupal will push a JSON version of the notification data to the RabbitMQ queue at admin.lissa.dev:15672
3. A supervisor process on admin.lissa.dev continuously monitors the RabbitMQ queue
4. The process will consume items posted to the queue and forwards them to admin.lissa.dev:8080/publish using the event uuid as channel name.
5. The nginx push stream server listens on /publish and will forward the payload to all clients connected on /ws/event-uuid or /subscribe/event-uuid

#### Data format

The notifications are returned in JSON format. Each message sent over the websocket is an object with the following properties:

- text: the notification entity data in JSON
- tag: the action (one of create, update or delete)
- id: an internal id to identify individual messages
- channel: the host event uuid
