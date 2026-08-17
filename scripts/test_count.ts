import { storage } from "../server/storage";

async function check() {
  await storage.initialize();
  const users = await storage.getUsers();
  console.log("TOTAL USERS IN FILESTORAGE:", users.length);
}

check();
