import { comparePasswords } from "../server/auth";

async function test() {
  const bcryptHash = "$2y$12$zzyY8L7u3fun7lDCOeZhF.wLBT6LNXUvTZ0ocSrkOXWYlg.r1iSga";
  const scryptHash = "6ace44738cfcf1f409765fefcb59e6d9252e7bbce72ec8651efc39618922e6336b9fcc47ffd7452d2c86b150f3e8847281df303b76b24e9ef57af013ae6bd330.a934fb5363506fe8256d40e701e4d556";
  
  console.log("Testing bcrypt hash with Admin@1234:", await comparePasswords("Admin@1234", bcryptHash));
  console.log("Testing scrypt hash with Admin@1234:", await comparePasswords("Admin@1234", scryptHash));
}

test();
