# GetSuperCP Cloud Deployment Guides

This document provides step-by-step deployment instructions for popular cloud providers.

---

## AWS EC2 Deployment

### Prerequisites

- AWS account with EC2 access
- AWS CLI installed locally
- SSH key pair created in EC2

### Step 1: Launch EC2 Instance

```bash
# Create security group
aws ec2 create-security-group \
  --group-name getsupercp-sg \
  --description "GetSuperCP security group"

# Add inbound rules
aws ec2 authorize-security-group-ingress \
  --group-name getsupercp-sg \
  --protocol tcp --port 22 --cidr 0.0.0.0/0  # SSH - restrict to your IP!

aws ec2 authorize-security-group-ingress \
  --group-name getsupercp-sg \
  --protocol tcp --port 80 --cidr 0.0.0.0/0  # HTTP

aws ec2 authorize-security-group-ingress \
  --group-name getsupercp-sg \
  --protocol tcp --port 443 --cidr 0.0.0.0/0  # HTTPS

# Launch instance (t3.medium recommended, Ubuntu 20.04 LTS)
aws ec2 run-instances \
  --image-id ami-0c02fb55ebf76d3d3 \
  --instance-type t3.medium \
  --security-groups getsupercp-sg \
  --key-name your-key-pair \
  --tag-specifications 'ResourceType=instance,Tags=[{Key=Name,Value=GetSuperCP}]'
```

### Step 2: Connect and Setup

```bash
# SSH into instance
ssh -i your-key.pem ubuntu@your-instance-ip

# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y curl wget git build-essential

# Install PHP 8.4
sudo apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-redis php8.4-curl

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install MySQL (or use AWS RDS instead)
sudo apt install -y mysql-server
```

### Step 3: Deploy GetSuperCP

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/yourusername/getsupercp.git
cd getsupercp

# Set ownership
sudo chown -R www-data:www-data .

# Install dependencies
composer install --no-dev

# Build frontend
npm ci && npm run build

# Run deployment script
sudo -u www-data ./deploy.sh production all
```

### Step 4: Configure Nginx

```bash
sudo tee /etc/nginx/sites-available/getsupercp > /dev/null << 'EOF'
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/getsupercp/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Security headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;

    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/getsupercp /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Restart
sudo systemctl restart nginx
```

### Step 5: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot certonly --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo systemctl enable certbot.timer
```

### Step 6: Database Setup

**Option A: AWS RDS (Recommended)**

```bash
# Create RDS instance through AWS Console
# MySQL 8.0, db.t3.micro minimum

# Connect from EC2
mysql -h your-rds-endpoint.rds.amazonaws.com -u admin -p

# Create database and user
CREATE DATABASE getsupercp;
CREATE USER 'getsupercp'@'%' IDENTIFIED BY 'strong_password';
GRANT ALL ON getsupercp.* TO 'getsupercp'@'%';
FLUSH PRIVILEGES;
```

**Option B: Local MySQL**

```bash
# Initialize database
sudo mysql_secure_installation

# Create database
mysql -u root -p << EOF
CREATE DATABASE getsupercp;
CREATE USER 'getsupercp'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL ON getsupercp.* TO 'getsupercp'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Step 7: Backup Configuration

```bash
# Add to crontab
(crontab -l 2>/dev/null; echo "0 3 * * * /var/www/getsupercp/deploy.sh production backup") | crontab -

# Enable automated backups in AWS S3
sudo apt install -y awscli

# Configure backup script
cat >> /var/www/getsupercp/backup-s3.sh << 'EOF'
#!/bin/bash
BACKUP_FILE="getsupercp-$(date +%Y%m%d-%H%M%S).sql"
mysqldump -h localhost -u getsupercp -p$DB_PASSWORD getsupercp > /tmp/$BACKUP_FILE
aws s3 cp /tmp/$BACKUP_FILE s3://your-bucket/backups/
rm /tmp/$BACKUP_FILE
EOF

chmod +x /var/www/getsupercp/backup-s3.sh
```

### Estimated Cost (Monthly)

- t3.medium EC2: ~$30
- RDS db.t3.micro: ~$20
- EBS storage: ~$10
- Data transfer: ~$5
- **Total: ~$65/month**

---

## Google Cloud Platform (GCP) Deployment

### Prerequisites

- Google Cloud account
- gcloud CLI installed
- Project created

### Step 1: Create Compute Instance

```bash
# Set project
gcloud config set project YOUR_PROJECT_ID

# Create instance
gcloud compute instances create getsupercp \
  --image-family=ubuntu-2004-lts \
  --image-project=ubuntu-os-cloud \
  --machine-type=e2-medium \
  --zone=us-central1-a \
  --scopes=https://www.googleapis.com/auth/cloud-platform

# Create firewall rules
gcloud compute firewall-rules create allow-http \
  --allow=tcp:80

gcloud compute firewall-rules create allow-https \
  --allow=tcp:443

gcloud compute firewall-rules create allow-ssh \
  --allow=tcp:22 \
  --source-ranges=YOUR_IP/32
```

### Step 2: SSH and Setup

```bash
# SSH into instance
gcloud compute ssh getsupercp --zone=us-central1-a

# Run setup (same as AWS)
sudo apt update && sudo apt upgrade -y
# ... (follow AWS installation steps above)
```

### Step 3: Use Cloud SQL

```bash
# Create Cloud SQL instance
gcloud sql instances create getsupercp-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1

# Create database and user
gcloud sql databases create getsupercp --instance=getsupercp-db

gcloud sql users create getsupercp \
  --instance=getsupercp-db \
  --password

# Connect from Compute instance
# Cloud SQL Proxy handles authentication automatically
```

### Step 4: Cloud Storage for Backups

```bash
# Create storage bucket
gsutil mb gs://getsupercp-backups

# Add backup script
cat >> backup-gcs.sh << 'EOF'
#!/bin/bash
BACKUP_FILE="getsupercp-$(date +%Y%m%d-%H%M%S).sql"
mysqldump -h 127.0.0.1 -u getsupercp -p$DB_PASSWORD getsupercp > /tmp/$BACKUP_FILE
gsutil cp /tmp/$BACKUP_FILE gs://getsupercp-backups/
rm /tmp/$BACKUP_FILE
EOF
```

### Estimated Cost (Monthly)

- Compute e2-medium: ~$25
- Cloud SQL db-f1-micro: ~$15
- Storage (backups): ~$5
- Network egress: ~$5
- **Total: ~$50/month**

---

## DigitalOcean Deployment

### Prerequisites

- DigitalOcean account
- SSH key added to account

### Step 1: Create Droplet

```bash
# Using doctl CLI
doctl compute droplet create getsupercp \
  --region sfo3 \
  --image ubuntu-20-04-x64 \
  --size s-2vcpu-4gb \
  --ssh-keys YOUR_SSH_KEY_ID \
  --enable-backups \
  --enable-monitoring
```

### Step 2: SSH and Setup

```bash
# SSH in
ssh root@YOUR_DROPLET_IP

# Setup (same as AWS above)
```

### Step 3: 1-Click Database (Managed Databases)

```bash
# Create managed database
doctl databases create getsupercp-db \
  --engine mysql \
  --region sfo3 \
  --num-nodes 1
```

### Step 4: Spaces for Backups

```bash
# Create Spaces bucket
doctl compute spaces create getsupercp-backups

# Configure backup script
```

### Estimated Cost (Monthly)

- Droplet $20
- Managed DB $15
- Backups $5
- **Total: ~$40/month**

---

## Azure Deployment

### Prerequisites

- Azure account
- Azure CLI installed

### Step 1: Create Resources

```bash
# Create resource group
az group create \
  --name getsupercp-rg \
  --location eastus

# Create VM
az vm create \
  --resource-group getsupercp-rg \
  --name getsupercp-vm \
  --image UbuntuLTS \
  --size Standard_B2s \
  --ssh-key-values @~/.ssh/id_rsa.pub

# Create database
az mysql server create \
  --resource-group getsupercp-rg \
  --name getsupercp-db \
  --location eastus \
  --admin-user dbadmin \
  --admin-password PASSWORD

# Get VM IP
az vm show -d \
  --resource-group getsupercp-rg \
  --name getsupercp-vm \
  --query publicIps -o tsv
```

### Step 2: Configure and Deploy

```bash
# SSH and setup (same as AWS)
```

### Step 3: Storage for Backups

```bash
# Create storage account
az storage account create \
  --name getsupercpbackups \
  --resource-group getsupercp-rg

# Create backup script for blob storage
```

### Estimated Cost (Monthly)

- VM Standard_B2s: ~$30
- MySQL Database: ~$25
- Storage: ~$5
- **Total: ~$60/month**

---

## Comparison Table

| Provider | Monthly Cost | Setup Time | Auto-scaling | Best For |
|----------|-------------|-----------|--------------|----------|
| AWS | $65 | 30 min | Yes | Enterprise |
| GCP | $50 | 25 min | Yes | Google Shop |
| DigitalOcean | $40 | 15 min | Limited | Startups |
| Azure | $60 | 30 min | Yes | Microsoft Shop |
| Linode | $35 | 15 min | Limited | Developers |

---

## Post-Deployment Checklist

For any cloud provider:

- [ ] Domain DNS configured
- [ ] SSL certificate installed
- [ ] Database migrated
- [ ] Files uploaded
- [ ] Backups configured and tested
- [ ] Monitoring setup
- [ ] Health checks running
- [ ] Firewall rules verified
- [ ] Load tested
- [ ] Security audit passed

---

## Scaling Your Deployment

### Horizontal Scaling

When you need to handle more users:

1. **Load Balancer**
   - AWS ELB / ALB
   - GCP Cloud Load Balancing
   - Azure Load Balancer

2. **Multiple App Servers**
   - Scale to 2-3 instances
   - Keep code in sync via Git
   - Share database and cache

3. **Separate Database Server**
   - Use managed databases (RDS, Cloud SQL, etc.)
   - Enable replication for failover
   - Optimize for read/write balance

4. **Cache Server**
   - Dedicated Redis instance
   - Elasticache / Cloud Memorystore
   - Session store

### Vertical Scaling

When you need more power per server:

1. Increase CPU cores (t3.medium → t3.large)
2. Increase RAM (4GB → 8GB or more)
3. Upgrade storage (100GB → 500GB)
4. Migrate to optimized instances (T → C or M family)

---

## Disaster Recovery

### Backup Strategy

```bash
# Daily backups
0 3 * * * /path/deploy.sh production backup

# Weekly to separate region
0 2 * * 0 aws s3 cp backup.sql s3://cross-region-bucket/
```

### Recovery Time Objective (RTO)

- Application: 30 minutes (restore from backup)
- Database: 15 minutes (from managed backup)
- Overall: 1 hour

### Recovery Point Objective (RPO)

- Daily backups = 24-hour RPO
- Consider hourly backups for critical systems

---

## Performance Optimization

### CDN Setup

```bash
# AWS CloudFront
aws cloudfront create-distribution \
  --origin-domain-name yourdomain.com \
  --default-root-object index.html
```

### Database Optimization

```sql
-- Add indexes
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_domain_user_id ON web_domains(user_id);
```

### Caching Strategy

- Static assets: CloudFront / CloudFlare (1 year TTL)
- API responses: Redis (5-60 minutes TTL)
- Database queries: Redis (5-30 minutes TTL)

---

## Cost Optimization Tips

1. **Use spot/preemptible instances** for non-critical workloads
2. **Reserved instances** for 1-3 year commitments (30-40% discount)
3. **Turn off resources** during off-peak hours
4. **Use CDN** to reduce bandwidth costs
5. **Compress** assets and enable gzip
6. **Monitor usage** and adjust sizing
7. **Use managed services** to reduce ops overhead

---

## Support & Resources

- AWS: https://aws.amazon.com/support
- GCP: https://cloud.google.com/support
- DigitalOcean: https://www.digitalocean.com/support
- Azure: https://azure.microsoft.com/en-us/support

Last Updated: January 4, 2026
