import os
from app import create_app
from flask_script import Manager, Server, Shell

# Initialize application and add it to the manager
app = create_app(os.getenv('FLASK_CONFIG') or 'default')
manager = Manager(app)

# Create commands
manager.add_command("runserver", Server(host="localhost", port=5000))
manager.add_command("shell", Shell(make_context=lambda: dict(app=app)))

if __name__ == '__main__':
    manager.run()
