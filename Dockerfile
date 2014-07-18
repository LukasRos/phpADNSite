FROM tutum/apache-php:latest
ADD . /app/
EXPOSE 80
CMD ["/run.sh"]
