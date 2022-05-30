FROM redis:7.0
ARG password
COPY redis.conf /usr/local/etc/redis/redis.conf
RUN echo "requirepass $password" >> /usr/local/etc/redis/redis.conf
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf" ]
