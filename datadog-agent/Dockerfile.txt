FROM datadog/agent:latest

COPY syslog.yaml /etc/datadog-agent/conf.d/syslog.d/conf.yaml