## About FusionApi

This is a simple API for extracting calls for use in a billing system. Authentication is handled by laravel sanctum, and php 8.0 is required on the FusionPBX server.
For deployment, check out the DEPLOYMENT.md file.

## Authorization

To authenticate an API request, you should provide your token in the `Authorization` header of each request.

You can obtain your token by calling  posting to either of the following routes.

```http
GET /api/login
```


```http
GET /api/register
```


## Responses

Many API endpoints return the JSON representation of the resources created or edited. However, if an invalid request is submitted, or some other error occurs, the API returns a JSON response in the following format:

```javascript
{
  "message" : string,
  "success" : bool,
  "data"    : string
}
```

The `message` attribute contains a message commonly used to indicate errors or any informational status messages.

The `success` attribute describes if the transaction was successful or not.

The `data` attribute contains any other metadata associated with the response. This will be an escaped string containing JSON data.

## Unprotected Routes

The following routes are available without first having a token:


### Login

Login to the API, this is required before calling any protected routes.

```http
POST /api/login
```

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `email` | `string` | **Required**. Your registered email address |
| `password` | `string` | **Required**. Your registered password |


#### Response

```javascript
{
    "user": {
        "id": integer,
        "name": string,
        "email": string,
        "email_verified_at": null,
        "created_at": datetime,
        "updated_at": datetime
    },
    "token": string
}
```

You will need to use the token returned from login route in subsequent requestts.

### Register

Register an account on the API, you will be logged in and provided a token.

```http
POST /api/register
```

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `name` | `string` | **Required**. Your name |
| `email` | `string` | **Required**. Your email address |
| `password` | `string` | **Required**. Your password (must meet complexity requirements) |
| `password_confirmation` | `string` | **Required**. Must match password |

#### Response

```javascript
{
    "user": {
        "id": integer,
        "name": string,
        "email": string,
        "email_verified_at": null,
        "created_at": datetime,
        "updated_at": datetime
    },
    "token": string
}
```

## Protected Routes

The following routes are protected and a bearer token must be provided (which are returned from the login and register routes)

### Logout

Your token will be deleted from the system, you will need to login again before calling any other protected routes.

```http
POST /api/logout
```

#### Response


```javascript
{
    "message": string,
}
```
### Get Active Calls

This will return all

### Get All Calls

This will return every call in the system for that domain_name... maybe you should use call_range instead?

```http
GET /api/calls
```

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `domain_name` | `string` | The domain name of the company whose calls you want to retrieve |


#### Response

```javascript
[
    {
        "xml_cdr_uuid": string,
        "domain_uuid": string,
        "extension_uuid": string,
        "sip_call_id": string,
        "domain_name": string,
        "accountcode": string,
        "direction": string,
        "default_language": string,
        "context": string,
        "caller_id_name": string,
        "caller_id_number": string,
        "caller_destination": string,
        "source_number": string,
        "destination_number": string,
        "start_epoch": string,
        "start_stamp": datetime,
        "answer_stamp": datetime,
        "answer_epoch": string,
        "end_epoch": string,
        "end_stamp": datetime,
        "duration": integer,
        "mduration": integer,
        "billsec": integer,
        "billmsec": integer,
        "bridge_uuid": string,
        "read_codec": string,
        "read_rate": string,
        "write_codec": string,
        "write_rate": string,
        "remote_media_ip": string,
        "network_addr": string,
        "record_path": string,
        "record_name": string,
        "leg": string,
        "originating_leg_uuid": string,
        "pdd_ms": integer,
        "rtp_audio_in_mos": float,
        "last_app": string,
        "last_arg": string,
        "voicemail_message": boolean,
        "missed_call": boolean,
        "call_center_queue_uuid": string,
        "cc_side": string,
        "cc_member_uuid": string,
        "cc_queue_joined_epoch": string,
        "cc_queue": string,
        "cc_member_session_uuid": string,
        "cc_agent_uuid": string,
        "cc_agent": string,
        "cc_agent_type": string,
        "cc_agent_bridged": string,
        "cc_queue_answered_epoch": string,
        "cc_queue_terminated_epoch": string,
        "cc_queue_canceled_epoch": string,
        "cc_cancel_reason": string,
        "cc_cause": string,
        "waitsec": integer,
        "conference_name": string,
        "conference_uuid": string,
        "conference_member_id": string,
        "digits_dialed": string,
        "pin_number": string,
        "hangup_cause": string,
        "hangup_cause_q850": string,
        "sip_hangup_disposition": string,
        "xml": string
    },
    ...
]
```

### Get Call Range

This will return all calls in the date range provided for the given domain.

```http
GET /api/call_range
```

| Parameter | Type | Description |
| :--- | :--- | :--- |
| `from` | `string` | The start of the date range to retrieve |
| `to` | `string` | The end of the date range to retrieve |
| `domain_name` | `string` | The domain name of the company whose calls you want to retrieve |

#### Response

```javascript
[
    {
        "xml_cdr_uuid": string,
        "domain_uuid": string,
        "extension_uuid": string,
        "sip_call_id": string,
        "domain_name": string,
        "accountcode": string,
        "direction": string,
        "default_language": string,
        "context": string,
        "caller_id_name": string,
        "caller_id_number": string,
        "caller_destination": string,
        "source_number": string,
        "destination_number": string,
        "start_epoch": string,
        "start_stamp": datetime,
        "answer_stamp": datetime,
        "answer_epoch": string,
        "end_epoch": string,
        "end_stamp": datetime,
        "duration": integer,
        "mduration": integer,
        "billsec": integer,
        "billmsec": integer,
        "bridge_uuid": string,
        "read_codec": string,
        "read_rate": string,
        "write_codec": string,
        "write_rate": string,
        "remote_media_ip": string,
        "network_addr": string,
        "record_path": string,
        "record_name": string,
        "leg": string,
        "originating_leg_uuid": string,
        "pdd_ms": integer,
        "rtp_audio_in_mos": float,
        "last_app": string,
        "last_arg": string,
        "voicemail_message": boolean,
        "missed_call": boolean,
        "call_center_queue_uuid": string,
        "cc_side": string,
        "cc_member_uuid": string,
        "cc_queue_joined_epoch": string,
        "cc_queue": string,
        "cc_member_session_uuid": string,
        "cc_agent_uuid": string,
        "cc_agent": string,
        "cc_agent_type": string,
        "cc_agent_bridged": string,
        "cc_queue_answered_epoch": string,
        "cc_queue_terminated_epoch": string,
        "cc_queue_canceled_epoch": string,
        "cc_cancel_reason": string,
        "cc_cause": string,
        "waitsec": integer,
        "conference_name": string,
        "conference_uuid": string,
        "conference_member_id": string,
        "digits_dialed": string,
        "pin_number": string,
        "hangup_cause": string,
        "hangup_cause_q850": string,
        "sip_hangup_disposition": string,
        "xml": string
    },
    ...
]
```

