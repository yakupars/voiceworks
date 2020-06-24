# Voiceworks Challenge

This project is created for [Voiceworks](https://voiceworks.com/en/) for testing purpose only.

## Installation
There are 3 different way to test this api;

- If you have docker installed this is the recommended way;
```bash
docker run --rm -p 8000:8000 yakupars/voiceworks:latest
```
- If you have symfony binary installed;
```bash
$ git clone https://github.com/yakupars/voiceworks && cd voiceworks
$ composer install
$ symfony serve --port 8000 --allow-http --no-tls
```

- If you have php binary installed;
```bash
$ git clone https://github.com/yakupars/voiceworks && cd voiceworks
$ composer install
$ php -S 0.0.0.0:8000 -t public
```

## Explanation
Two different approach applied while creating the api, hence there are two different endpoints.

```url
http://127.0.0.1:8000/v1/message
http://127.0.0.1:8000/v2/message
```

### V1 Approach
In the first approach api gets the post request body, finds the request type,
calculates the response type and **creates xml from response xsd file**. This is
a good method if you have the time to create a composer package which parses
the ***scheme file to xml*** itself. There was no proper package to manage 
this situation that I can find of so write some services to temporarily solve the
problem. This method ensures response validity without scheme validation because
xml is already created by xsd rules.

- A generic controller which gets the request and creates response using given services 
  - src/Controller/V1/MessageController.php
- A service to parse xsd file and create an array. That array will be used to create xml later.
  - src/Service/XsdToXmlConverterService.php
- Where the business logic of the requests lives. If you want to add new request to parse,
then you need to add the method to this class along with the xsd files to the 
xsd directory
  - xsd/*
  - src/Service/ResponseService.php


### V2 Approach *
This approach uses more object oriented way. In V2 project relies on a yaml file
to map the request to response and request processors. Request processors are the
classes where the given request's business logic lives. This approach uses
***symfony/serializer*** package havily for encoding and / or normalizing
requests and responses.

This approach requires creating ***class/object*** representations of the requests
and responses.

- A generic controller which gets the request and creates response using given services 
  - src/Controller/V2/MessageController.php
- Dao classes are the object representation of the xml strings (Requests and Response bodies)
If you need to add new request type you need to create new class for it.
  - src/Dao/*
- Api must know which request type is related to which xsd file and which request processor.
When you need to add new request you must add this relation to given config file.
  - config/daomap.yaml
- Every request has its own processor. After config map and dao creation, you can process the request here.
Api uses factory design pattern to create new processor by its name.
  - src/RequestProcess/*
- Every message (Request and Response) processed by this service.
  - src/Service/MessageProcessService.php
  
There are many components are used like Interfaces and Exceptions to manage the flow and data integrity 
that I do not think needs explanation.

#### Note 
In V2 you can use this endpoint to simulate the fail response; 
```url
http://127.0.0.1:8000/v2/message?testfail=1
```

Functional and Unit test are runs by;
```bash
bin/phpunit 
```


>with **symfony** by **yakupars** for **fun**