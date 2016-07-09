# Save100

A Slack bot that returns a GitHub Gist with the past 100 messages in response to a `/save100` command.

When a Slack user executes a `/save100` command, a POST request is sent to the app. The app then collects the past 100 messages in the channel the command was used within as well as team and channel information before returning a URL to the GitHub Gist.
