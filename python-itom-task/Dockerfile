FROM python:2-alpine

ENV ANSIBLE_HOST_KEY_CHECKING=False
ENV SERVICE_PATH ./python-itom-task
COPY $SERVICE_PATH/ /app/

WORKDIR /app

RUN apk update &&\
    apk add --no-cache gcc musl-dev libffi-dev openssl-dev make openssh &&\
    pip install -r requirements.txt &&\
    mkdir /app/log

CMD ["python", "bin/itom_task.py"]